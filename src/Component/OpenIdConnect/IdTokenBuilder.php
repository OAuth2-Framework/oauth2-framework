<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect;

use Base64Url\Base64Url;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class IdTokenBuilder
{
    /**
     * @var string
     */
    private $issuer;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserAccount
     */
    private $userAccount;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var UserInfo
     */
    private $userinfo;

    /**
     * @var JWKSet
     */
    private $signatureKeys;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var string[]
     */
    private $scopes = [];

    /**
     * @var array
     */
    private $requestedClaims = [];

    /**
     * @var string|null
     */
    private $claimsLocales = null;

    /**
     * @var AccessTokenId|null
     */
    private $accessTokenId = null;

    /**
     * @var AuthorizationCodeId|null
     */
    private $authorizationCodeId = null;

    /**
     * @var string|null
     */
    private $nonce = null;

    /**
     * @var bool
     */
    private $withAuthenticationTime = false;

    /**
     * @var JWSBuilder|null
     */
    private $jwsBuilder = null;

    /**
     * @var string|null
     */
    private $signatureAlgorithm = null;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder;

    /**
     * @var string|null
     */
    private $keyEncryptionAlgorithm = null;

    /**
     * @var string|null
     */
    private $contentEncryptionAlgorithm = null;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt = null;

    /**
     * IdTokenBuilder constructor.
     *
     * @param string      $issuer
     * @param UserInfo    $userinfo
     * @param int         $lifetime
     * @param Client      $client
     * @param UserAccount $userAccount
     * @param string      $redirectUri
     */
    private function __construct(string $issuer, UserInfo $userinfo, int $lifetime, Client $client, UserAccount $userAccount, string $redirectUri)
    {
        $this->issuer = $issuer;
        $this->userinfo = $userinfo;
        $this->lifetime = $lifetime;
        $this->client = $client;
        $this->userAccount = $userAccount;
        $this->redirectUri = $redirectUri;
    }

    /**
     * @param string      $issuer
     * @param UserInfo    $userinfo
     * @param int         $lifetime
     * @param Client      $client
     * @param UserAccount $userAccount
     * @param string      $redirectUri
     *
     * @return IdTokenBuilder
     */
    public static function create(string $issuer, UserInfo $userinfo, int $lifetime, Client $client, UserAccount $userAccount, string $redirectUri)
    {
        return new self($issuer, $userinfo, $lifetime, $client, $userAccount, $redirectUri);
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return IdTokenBuilder
     */
    public function withAccessToken(AccessToken $accessToken): self
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessToken->getTokenId();
        $clone->expiresAt = $accessToken->getExpiresAt();
        $clone->scopes = $accessToken->getScopes();

        if ($accessToken->hasMetadata('code')) {
            $authorizationCode = $accessToken->getMetadata('code');
            $clone->authorizationCodeId = $authorizationCode->getTokenId();
            $queryParams = $authorizationCode->getQueryParams();
            foreach (['nonce' => 'nonce', 'claims_locales' => 'claimsLocales'] as $k => $v) {
                if (array_key_exists($k, $queryParams)) {
                    $clone->$v = $queryParams[$k];
                }
            }
            $clone->withAuthenticationTime = array_key_exists('max_age', $authorizationCode->getQueryParams());
        }

        return $clone;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return IdTokenBuilder
     */
    public function withAccessTokenId(AccessTokenId $accessTokenId): self
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessTokenId;

        return $clone;
    }

    /**
     * @param AuthorizationCodeId $authorizationCodeId
     *
     * @return IdTokenBuilder
     */
    public function withAuthorizationCodeId(AuthorizationCodeId $authorizationCodeId): self
    {
        $clone = clone $this;
        $clone->authorizationCodeId = $authorizationCodeId;

        return $clone;
    }

    /**
     * @param string $claimsLocales
     *
     * @return IdTokenBuilder
     */
    public function withClaimsLocales(string $claimsLocales): self
    {
        $clone = clone $this;
        $clone->claimsLocales = $claimsLocales;

        return $clone;
    }

    /**
     * @return IdTokenBuilder
     */
    public function withAuthenticationTime(): self
    {
        $clone = clone $this;
        $clone->withAuthenticationTime = true;

        return $clone;
    }

    /**
     * @param string[] $scopes
     *
     * @return IdTokenBuilder
     */
    public function withScope(array $scopes): self
    {
        $clone = clone $this;
        $clone->scopes = $scopes;

        return $clone;
    }

    /**
     * @param array $requestedClaims
     *
     * @return IdTokenBuilder
     */
    public function withRequestedClaims(array $requestedClaims): self
    {
        $clone = clone $this;
        $clone->requestedClaims = $requestedClaims;

        return $clone;
    }

    /**
     * @param string $nonce
     *
     * @return IdTokenBuilder
     */
    public function withNonce(string $nonce): self
    {
        $clone = clone $this;
        $clone->nonce = $nonce;

        return $clone;
    }

    /**
     * @param \DateTimeImmutable $expiresAt
     *
     * @return IdTokenBuilder
     */
    public function withExpirationAt(\DateTimeImmutable $expiresAt): self
    {
        $clone = clone $this;
        $clone->expiresAt = $expiresAt;

        return $clone;
    }

    /**
     * @return IdTokenBuilder
     */
    public function withoutAuthenticationTime(): self
    {
        $clone = clone $this;
        $clone->withAuthenticationTime = false;

        return $clone;
    }

    /**
     * @param JWSBuilder $jwsBuilder
     * @param JWKSet     $signatureKeys
     * @param string     $signatureAlgorithm
     *
     * @return IdTokenBuilder
     */
    public function withSignature(JWSBuilder $jwsBuilder, JWKSet $signatureKeys, string $signatureAlgorithm): self
    {
        if (!in_array($signatureAlgorithm, $jwsBuilder->getSignatureAlgorithmManager()->list())) {
            throw new \InvalidArgumentException(sprintf('Unsupported signature algorithm "%s". Please use one of the following one: %s', $signatureAlgorithm, implode(', ', $jwsBuilder->getSignatureAlgorithmManager()->list())));
        }
        if (0 === $signatureKeys->count()) {
            throw new \InvalidArgumentException('The signature key set must contain at least one key.');
        }
        $clone = clone $this;
        $clone->jwsBuilder = $jwsBuilder;
        $clone->signatureKeys = $signatureKeys;
        $clone->signatureAlgorithm = $signatureAlgorithm;

        return $clone;
    }

    /**
     * @param JWEBuilder $jweBuilder
     * @param string     $keyEncryptionAlgorithm
     * @param string     $contentEncryptionAlgorithm
     *
     * @return IdTokenBuilder
     */
    public function withEncryption(JWEBuilder $jweBuilder, string $keyEncryptionAlgorithm, string $contentEncryptionAlgorithm): self
    {
        if (!in_array($keyEncryptionAlgorithm, $jweBuilder->getKeyEncryptionAlgorithmManager()->list())) {
            throw new \InvalidArgumentException(sprintf('Unsupported key encryption algorithm "%s". Please use one of the following one: %s', $keyEncryptionAlgorithm, implode(', ', $jweBuilder->getKeyEncryptionAlgorithmManager()->list())));
        }
        if (!in_array($contentEncryptionAlgorithm, $jweBuilder->getContentEncryptionAlgorithmManager()->list())) {
            throw new \InvalidArgumentException(sprintf('Unsupported content encryption algorithm "%s". Please use one of the following one: %s', $contentEncryptionAlgorithm, implode(', ', $jweBuilder->getContentEncryptionAlgorithmManager()->list())));
        }
        $clone = clone $this;
        $clone->jweBuilder = $jweBuilder;
        $clone->keyEncryptionAlgorithm = $keyEncryptionAlgorithm;
        $clone->contentEncryptionAlgorithm = $contentEncryptionAlgorithm;

        return $clone;
    }

    /**
     * @return string
     */
    public function build(): string
    {
        $data = $this->userinfo->getUserinfo($this->client, $this->userAccount, $this->redirectUri, $this->requestedClaims, $this->scopes, $this->claimsLocales);
        $data = $this->updateClaimsWithAmrAndAcrInfo($data, $this->userAccount);
        $data = $this->updateClaimsWithAuthenticationTime($data, $this->userAccount);
        $data = $this->updateClaimsWithNonce($data);
        if (null !== $this->signatureAlgorithm) {
            $data = $this->updateClaimsWithJwtClaims($data);
            $data = $this->updateClaimsWithTokenHash($data);
            $data = $this->updateClaimsAudience($data);
            $result = $this->computeIdToken($data);
        } else {
            $result = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (null !== $this->keyEncryptionAlgorithm && null !== $this->contentEncryptionAlgorithm) {
            $result = $this->tryToEncrypt($this->client, $result);
        }

        return $result;
    }

    /**
     * @param array $claims
     *
     * @return array
     */
    private function updateClaimsWithJwtClaims(array $claims): array
    {
        if (null === $this->expiresAt) {
            $this->expiresAt = (new \DateTimeImmutable())->setTimestamp(time() + $this->lifetime);
        }
        $claims += [
            'iat' => time(),
            'nbf' => time(),
            'exp' => $this->expiresAt->getTimestamp(),
            'jti' => Base64Url::encode(random_bytes(25)),
            'iss' => $this->issuer,
        ];

        return $claims;
    }

    /**
     * @param array       $claims
     * @param UserAccount $userAccount
     *
     * @return array
     */
    private function updateClaimsWithAuthenticationTime(array $claims, UserAccount $userAccount): array
    {
        if (true === $this->withAuthenticationTime && null !== $userAccount->getLastLoginAt()) { //FIXME: check if the client has a require_auth_time parameter
            $claims['auth_time'] = $userAccount->getLastLoginAt()->getTimestamp();
        }

        return $claims;
    }

    /**
     * @param array $claims
     *
     * @return array
     */
    private function updateClaimsWithNonce(array $claims): array
    {
        if (null !== $this->nonce) {
            $claims['nonce'] = $this->nonce;
        }

        return $claims;
    }

    /**
     * @param array $claims
     *
     * @return array
     */
    private function updateClaimsAudience(array $claims): array
    {
        $claims['aud'] = [
            $this->client->getPublicId()->getValue(),
            $this->issuer,
        ];
        $claims['azp'] = $this->client->getPublicId()->getValue();

        return $claims;
    }

    /**
     * @param array       $claims
     * @param UserAccount $userAccount
     *
     * @return array
     */
    private function updateClaimsWithAmrAndAcrInfo(array $claims, UserAccount $userAccount): array
    {
        foreach (['amr' => 'amr', 'acr' => 'acr'] as $claim => $key) {
            if ($userAccount->has($claim)) {
                $claims[$key] = $userAccount->get($claim);
            }
        }

        return $claims;
    }

    /**
     * @param array $claims
     *
     * @return string
     */
    private function computeIdToken(array $claims): string
    {
        $signatureKey = $this->getSignatureKey($this->signatureAlgorithm);
        $header = $this->getHeaders($signatureKey, $this->signatureAlgorithm);
        $jsonConverter = new StandardConverter();
        $claims = $jsonConverter->encode($claims);
        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($claims)
            ->addSignature($signatureKey, $header)
            ->build();
        $serializer = new JwsCompactSerializer($jsonConverter);

        return $serializer->serialize($jws, 0);
    }

    /**
     * @param Client $client
     * @param string $jwt
     *
     * @return string
     */
    private function tryToEncrypt(Client $client, string $jwt): string
    {
        $clientKeySet = $client->getPublicKeySet();
        $keyEncryptionAlgorithm = $this->jweBuilder->getKeyEncryptionAlgorithmManager()->get($this->keyEncryptionAlgorithm);
        $encryptionKey = $clientKeySet->selectKey('enc', $keyEncryptionAlgorithm);
        if (null === $encryptionKey) {
            throw new \InvalidArgumentException('No encryption key available for the client.');
        }
        $header = [
            'typ' => 'JWT',
            'jti' => Base64Url::encode(random_bytes(25)),
            'alg' => $this->keyEncryptionAlgorithm,
            'enc' => $this->contentEncryptionAlgorithm,
        ];
        $jwe = $this->jweBuilder
            ->create()
            ->withPayload($jwt)
            ->withSharedProtectedHeader($header)
            ->addRecipient($encryptionKey)
            ->build();
        $jsonConverter = new StandardConverter();
        $serializer = new JweCompactSerializer($jsonConverter);

        return $serializer->serialize($jwe, 0);
    }

    /**
     * @param string $signatureAlgorithm
     *
     * @return JWK
     */
    private function getSignatureKey(string $signatureAlgorithm): JWK
    {
        $signatureAlgorithm = $this->jwsBuilder->getSignatureAlgorithmManager()->get($signatureAlgorithm);
        if ('none' === $signatureAlgorithm->name()) {
            return JWK::create(['kty' => 'none', 'alg' => 'none', 'use' => 'sig']);
        }
        $signatureKey = $this->signatureKeys->selectKey('sig', $signatureAlgorithm);
        if (null === $signatureKey) {
            throw new \InvalidArgumentException('Unable to find a key to sign the ID Token. Please verify the selected key set contains suitable keys.');
        }

        return $signatureKey;
    }

    /**
     * @param JWK    $signatureKey
     * @param string $signatureAlgorithm
     *
     * @return array
     */
    private function getHeaders(JWK $signatureKey, string $signatureAlgorithm): array
    {
        $header = [
            'typ' => 'JWT',
            'alg' => $signatureAlgorithm,
        ];
        if ($signatureKey->has('kid')) {
            $header['kid'] = $signatureKey->get('kid');
        }

        return $header;
    }

    /**
     * @param array $claims
     *
     * @return array
     */
    private function updateClaimsWithTokenHash(array $claims): array
    {
        if ('none' === $this->signatureAlgorithm) {
            return $claims;
        }
        if (null !== $this->accessTokenId) {
            $claims['at_hash'] = $this->getHash($this->accessTokenId);
        }
        if (null !== $this->authorizationCodeId) {
            $claims['c_hash'] = $this->getHash($this->authorizationCodeId);
        }

        return $claims;
    }

    /**
     * @param TokenId $tokenId
     *
     * @return string
     */
    private function getHash(TokenId $tokenId): string
    {
        return Base64Url::encode(mb_substr(hash($this->getHashMethod(), $tokenId->getValue(), true), 0, $this->getHashSize(), '8bit'));
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    private function getHashMethod(): string
    {
        $map = [
            'HS256' => 'sha256',
            'ES256' => 'sha256',
            'RS256' => 'sha256',
            'PS256' => 'sha256',
            'HS384' => 'sha384',
            'ES384' => 'sha384',
            'RS384' => 'sha384',
            'PS384' => 'sha384',
            'HS512' => 'sha512',
            'ES512' => 'sha512',
            'RS512' => 'sha512',
            'PS512' => 'sha512',
        ];

        if (!array_key_exists($this->signatureAlgorithm, $map)) {
            throw new \InvalidArgumentException(sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return int
     */
    private function getHashSize(): int
    {
        $map = [
            'HS256' => 16,
            'ES256' => 16,
            'RS256' => 16,
            'PS256' => 16,
            'HS384' => 24,
            'ES384' => 24,
            'RS384' => 24,
            'PS384' => 24,
            'HS512' => 32,
            'ES512' => 32,
            'RS512' => 32,
            'PS512' => 32,
        ];

        if (!array_key_exists($this->signatureAlgorithm, $map)) {
            throw new \InvalidArgumentException(sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }
}
