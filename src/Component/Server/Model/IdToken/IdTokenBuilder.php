<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\IdToken;

use Assert\Assertion;
use Base64Url\Base64Url;
use Jose\EncrypterInterface;
use Jose\Factory\JWEFactory;
use Jose\Factory\JWSFactory;
use Jose\SignerInterface;
use Jose\Object\JWKInterface;
use Jose\Object\JWKSetInterface;
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Token\TokenId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountInterface;

final class IdTokenBuilder
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
     * @var UserAccountInterface
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
     * @var JWKSetInterface
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
     * @var AuthCodeId|null
     */
    private $authCodeId = null;

    /**
     * @var string|null
     */
    private $nonce = null;

    /**
     * @var bool
     */
    private $withAuthenticationTime = false;

    /**
     * @var SignerInterface|null
     */
    private $signer = null;

    /**
     * @var string|null
     */
    private $signatureAlgorithm = null;

    /**
     * @var EncrypterInterface|null
     */
    private $encrypter;

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
     * @param string               $issuer
     * @param UserInfo             $userinfo
     * @param int                  $lifetime
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param string               $redirectUri
     */
    private function __construct(string $issuer, UserInfo $userinfo, int $lifetime, Client $client, UserAccountInterface $userAccount, string $redirectUri)
    {
        $this->issuer = $issuer;
        $this->userinfo = $userinfo;
        $this->lifetime = $lifetime;
        $this->client = $client;
        $this->userAccount = $userAccount;
        $this->redirectUri = $redirectUri;
    }

    /**
     * @param string               $issuer
     * @param UserInfo             $userinfo
     * @param int                  $lifetime
     * @param Client               $client
     * @param UserAccountInterface $userAccount
     * @param string               $redirectUri
     *
     * @return IdTokenBuilder
     */
    public static function create(string $issuer, UserInfo $userinfo, int $lifetime, Client $client, UserAccountInterface $userAccount, string $redirectUri)
    {
        return new self($issuer, $userinfo, $lifetime, $client, $userAccount, $redirectUri);
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return IdTokenBuilder
     */
    public function withAccessToken(AccessToken $accessToken): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessToken->getTokenId();
        $clone->expiresAt = $accessToken->getExpiresAt();
        $clone->scopes = $accessToken->getScopes();

        if ($accessToken->hasMetadata('code')) {
            $authCode = $accessToken->getMetadata('code');
            $clone->authCodeId = $authCode->getTokenId();
            $queryParams = $authCode->getQueryParams();
            foreach (['nonce' => 'nonce', 'claims_locales' => 'claimsLocales'] as $k => $v) {
                if (array_key_exists($k, $queryParams)) {
                    $clone->$v = $queryParams[$k];
                }
            }
            $clone->withAuthenticationTime = array_key_exists('max_age', $authCode->getQueryParams());
        }

        return $clone;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return IdTokenBuilder
     */
    public function withAccessTokenId(AccessTokenId $accessTokenId): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessTokenId;

        return $clone;
    }

    /**
     * @param AuthCodeId $authCodeId
     *
     * @return IdTokenBuilder
     */
    public function withAuthCodeId(AuthCodeId $authCodeId): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->authCodeId = $authCodeId;

        return $clone;
    }

    /**
     * @param string $claimsLocales
     *
     * @return IdTokenBuilder
     */
    public function withClaimsLocales(string $claimsLocales): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->claimsLocales = $claimsLocales;

        return $clone;
    }

    /**
     * @return IdTokenBuilder
     */
    public function withAuthenticationTime(): IdTokenBuilder
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
    public function withScope(array $scopes): IdTokenBuilder
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
    public function withRequestedClaims(array $requestedClaims): IdTokenBuilder
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
    public function withNonce(string $nonce): IdTokenBuilder
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
    public function withExpirationAt(\DateTimeImmutable $expiresAt): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->expiresAt = $expiresAt;

        return $clone;
    }

    /**
     * @return IdTokenBuilder
     */
    public function withoutAuthenticationTime(): IdTokenBuilder
    {
        $clone = clone $this;
        $clone->withAuthenticationTime = false;

        return $clone;
    }

    /**
     * @param SignerInterface $signer
     * @param JWKSetInterface $signatureKeys
     * @param string          $signatureAlgorithm
     *
     * @return IdTokenBuilder
     */
    public function withSignature(SignerInterface $signer, JWKSetInterface $signatureKeys, string $signatureAlgorithm): IdTokenBuilder
    {
        Assertion::inArray($signatureAlgorithm, $signer->getSupportedSignatureAlgorithms(), sprintf('Unsupported signature algorithm \'%s\'. Please use one of the following one: %s', $signatureAlgorithm, implode(', ', $signer->getSupportedSignatureAlgorithms())));
        Assertion::true(0 !== $signatureKeys->countKeys(), 'The signature key set must contain at least one key.');
        $clone = clone $this;
        $clone->signer = $signer;
        $clone->signatureKeys = $signatureKeys;
        $clone->signatureAlgorithm = $signatureAlgorithm;

        return $clone;
    }

    /**
     * @param EncrypterInterface $encrypter
     * @param string             $keyEncryptionAlgorithm
     * @param string             $contentEncryptionAlgorithm
     *
     * @return IdTokenBuilder
     */
    public function withEncryption(EncrypterInterface $encrypter, string $keyEncryptionAlgorithm, string $contentEncryptionAlgorithm): IdTokenBuilder
    {
        Assertion::inArray($keyEncryptionAlgorithm, $encrypter->getSupportedKeyEncryptionAlgorithms(), sprintf('Unsupported key encryption algorithm \'%s\'. Please use one of the following one: %s', $keyEncryptionAlgorithm, implode(', ', $encrypter->getSupportedKeyEncryptionAlgorithms())));
        Assertion::inArray($contentEncryptionAlgorithm, $encrypter->getSupportedContentEncryptionAlgorithms(), sprintf('Unsupported key encryption algorithm \'%s\'. Please use one of the following one: %s', $contentEncryptionAlgorithm, implode(', ', $encrypter->getSupportedContentEncryptionAlgorithms())));
        $clone = clone $this;
        $clone->encrypter = $encrypter;
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
            $result = $this->computeIdToken($data);
        } else {
            $result = json_encode($data);
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
            $time = sprintf('now +%s sec', $this->lifetime);
            $this->expiresAt = new \DateTimeImmutable($time);
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
     * @param array                $claims
     * @param UserAccountInterface $userAccount
     *
     * @return array
     */
    private function updateClaimsWithAuthenticationTime(array $claims, UserAccountInterface $userAccount): array
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
     * @param array                $claims
     * @param UserAccountInterface $userAccount
     *
     * @return array
     */
    private function updateClaimsWithAmrAndAcrInfo(array $claims, UserAccountInterface $userAccount): array
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
        $headers = $this->getHeaders($signatureKey, $this->signatureAlgorithm);
        $jws = JWSFactory::createJWS($claims);
        $jws = $jws->addSignatureInformation($signatureKey, $headers);
        $this->signer->sign($jws);

        return $jws->toCompactJSON(0);
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
        $encryptionKey = $clientKeySet->selectKey('enc', $this->keyEncryptionAlgorithm);
        Assertion::notNull($encryptionKey, 'No encryption key available for the client.');
        $headers = [
            'typ' => 'JWT',
            'jti' => Base64Url::encode(random_bytes(25)),
            'alg' => $this->keyEncryptionAlgorithm,
            'enc' => $this->contentEncryptionAlgorithm,
        ];
        $jwe = JWEFactory::createJWE($jwt, $headers);
        $jwe = $jwe->addRecipientInformation($encryptionKey);
        $this->encrypter->encrypt($jwe);

        return $jwe->toCompactJSON(0);
    }

    /**
     * @param string $signatureAlgorithm
     *
     * @return JWKInterface
     */
    private function getSignatureKey(string $signatureAlgorithm): JWKInterface
    {
        $signatureKey = $this->signatureKeys->selectKey('sig', $signatureAlgorithm);
        Assertion::notNull($signatureKey, 'Unable to find a key to sign the ID Token. Please verify the selected key set contains suitable keys.');

        return $signatureKey;
    }

    /**
     * @param JWKInterface $signatureKey
     * @param string       $signatureAlgorithm
     *
     * @return array
     */
    private function getHeaders(JWKInterface $signatureKey, string $signatureAlgorithm): array
    {
        $headers = [
            'typ' => 'JWT',
            'alg' => $signatureAlgorithm,
        ];
        if ($signatureKey->has('kid')) {
            $headers['kid'] = $signatureKey->get('kid');
        }

        return $headers;
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
        if (null !== $this->authCodeId) {
            $claims['c_hash'] = $this->getHash($this->authCodeId);
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

        Assertion::keyExists($map, $this->signatureAlgorithm, sprintf('Algorithm \'%s\' is not supported', $this->signatureAlgorithm));

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

        Assertion::keyExists($map, $this->signatureAlgorithm, sprintf('Algorithm \'%s\' is not supported', $this->signatureAlgorithm));

        return $map[$this->signatureAlgorithm];
    }
}
