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
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\KeyManagement\JKUFactory;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\User\User;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class IdTokenBuilder
{
    private $issuer;
    private $client;
    private $user;
    private $userAccount;
    private $redirectUri;
    private $userinfo;
    private $signatureKeys;
    private $lifetime;
    private $scope = null;
    private $requestedClaims = [];
    private $claimsLocales = null;
    private $accessTokenId = null;
    private $authorizationCodeId = null;
    private $nonce = null;
    private $withAuthenticationTime = false;
    private $jwsBuilder = null;
    private $signatureAlgorithm = null;
    private $jweBuilder;
    private $keyEncryptionAlgorithm = null;
    private $contentEncryptionAlgorithm = null;
    private $expiresAt = null;
    private $jkuFactory = null;
    private $authorizationCodeRepository = null;

    public function __construct(string $issuer, UserInfo $userinfo, int $lifetime, Client $client, User $user, UserAccount $userAccount, string $redirectUri, ?JKUFactory $jkuFactory, ?AuthorizationCodeRepository $authorizationCodeRepository)
    {
        $this->issuer = $issuer;
        $this->userinfo = $userinfo;
        $this->lifetime = $lifetime;
        $this->client = $client;
        $this->user = $user;
        $this->userAccount = $userAccount;
        $this->redirectUri = $redirectUri;
        $this->jkuFactory = $jkuFactory;
        $this->authorizationCodeRepository = $authorizationCodeRepository;
    }

    public function setAccessToken(AccessToken $accessToken): void
    {
        $this->accessTokenId = $accessToken->getTokenId();
        $this->expiresAt = $accessToken->getExpiresAt();
        $this->scope = $accessToken->getParameter()->has('scope') ? $accessToken->getParameter()->get('scope') : null;

        if ($accessToken->getMetadata()->has('authorization_code_id') && null !== $this->authorizationCodeRepository) {
            $authorizationCodeId = new AuthorizationCodeId($accessToken->getMetadata()->get('authorization_code_id'));
            $authorizationCode = $this->authorizationCodeRepository->find($authorizationCodeId);
            if (null === $authorizationCode) {
                return;
            }
            $this->authorizationCodeId = $authorizationCodeId;
            $queryParams = $authorizationCode->getQueryParams();
            foreach (['nonce' => 'nonce', 'claims_locales' => 'claimsLocales'] as $k => $v) {
                if (\array_key_exists($k, $queryParams)) {
                    $this->$v = $queryParams[$k];
                }
            }
            $this->withAuthenticationTime = \array_key_exists('max_age', $authorizationCode->getQueryParams());
        }
    }

    public function withAccessTokenId(AccessTokenId $accessTokenId): void
    {
        $this->accessTokenId = $accessTokenId;
    }

    public function withAuthorizationCodeId(AuthorizationCodeId $authorizationCodeId): void
    {
        $this->authorizationCodeId = $authorizationCodeId;
    }

    public function withClaimsLocales(string $claimsLocales): void
    {
        $this->claimsLocales = $claimsLocales;
    }

    public function withAuthenticationTime(): void
    {
        $this->withAuthenticationTime = true;
    }

    public function withScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function withRequestedClaims(array $requestedClaims): void
    {
        $this->requestedClaims = $requestedClaims;
    }

    public function withNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function withExpirationAt(\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function withoutAuthenticationTime(): void
    {
        $this->withAuthenticationTime = false;
    }

    public function withSignature(JWSBuilder $jwsBuilder, JWKSet $signatureKeys, string $signatureAlgorithm): void
    {
        if (!\in_array($signatureAlgorithm, $jwsBuilder->getSignatureAlgorithmManager()->list(), true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Unsupported signature algorithm "%s". Please use one of the following one: %s', $signatureAlgorithm, \implode(', ', $jwsBuilder->getSignatureAlgorithmManager()->list())));
        }
        if (0 === $signatureKeys->count()) {
            throw new \InvalidArgumentException('The signature key set must contain at least one key.');
        }
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
        $this->signatureAlgorithm = $signatureAlgorithm;
    }

    public function withEncryption(JWEBuilder $jweBuilder, string $keyEncryptionAlgorithm, string $contentEncryptionAlgorithm): void
    {
        if (!\in_array($keyEncryptionAlgorithm, $jweBuilder->getKeyEncryptionAlgorithmManager()->list(), true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Unsupported key encryption algorithm "%s". Please use one of the following one: %s', $keyEncryptionAlgorithm, \implode(', ', $jweBuilder->getKeyEncryptionAlgorithmManager()->list())));
        }
        if (!\in_array($contentEncryptionAlgorithm, $jweBuilder->getContentEncryptionAlgorithmManager()->list(), true)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Unsupported content encryption algorithm "%s". Please use one of the following one: %s', $contentEncryptionAlgorithm, \implode(', ', $jweBuilder->getContentEncryptionAlgorithmManager()->list())));
        }
        $this->jweBuilder = $jweBuilder;
        $this->keyEncryptionAlgorithm = $keyEncryptionAlgorithm;
        $this->contentEncryptionAlgorithm = $contentEncryptionAlgorithm;
    }

    public function build(): string
    {
        if (null === $this->scope) {
            throw new \LogicException('It is mandatory to set the scope.');
        }
        $data = $this->userinfo->getUserinfo($this->client, $this->user, $this->userAccount, $this->redirectUri, $this->requestedClaims, $this->scope, $this->claimsLocales);
        //$data = $this->updateClaimsWithAmrAndAcrInfo($data, $this->userAccount);
        $data = $this->updateClaimsWithAuthenticationTime($data, $this->user, $this->requestedClaims);
        $data = $this->updateClaimsWithNonce($data);
        if (null !== $this->signatureAlgorithm) {
            $data = $this->updateClaimsWithJwtClaims($data);
            $data = $this->updateClaimsWithTokenHash($data);
            $data = $this->updateClaimsAudience($data);
            $result = $this->computeIdToken($data);
        } else {
            $result = \Safe\json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (null !== $this->keyEncryptionAlgorithm && null !== $this->contentEncryptionAlgorithm) {
            $result = $this->tryToEncrypt($this->client, $result);
        }

        return $result;
    }

    private function updateClaimsWithJwtClaims(array $claims): array
    {
        if (null === $this->expiresAt) {
            $this->expiresAt = (new \DateTimeImmutable())->setTimestamp(\time() + $this->lifetime);
        }
        $claims += [
            'iat' => \time(),
            'nbf' => \time(),
            'exp' => $this->expiresAt->getTimestamp(),
            'jti' => Base64Url::encode(\random_bytes(16)),
            'iss' => $this->issuer,
        ];

        return $claims;
    }

    private function updateClaimsWithAuthenticationTime(array $claims, User $user, array $requestedClaims): array
    {
        if ((true === $this->withAuthenticationTime || \array_key_exists('auth_time', $requestedClaims)) && null !== $user->getLastLoginAt()) {
            $claims['auth_time'] = $user->getLastLoginAt();
        }

        return $claims;
    }

    private function updateClaimsWithNonce(array $claims): array
    {
        if (null !== $this->nonce) {
            $claims['nonce'] = $this->nonce;
        }

        return $claims;
    }

    private function updateClaimsAudience(array $claims): array
    {
        $claims['aud'] = [
            $this->client->getPublicId()->getValue(),
            $this->issuer,
        ];
        $claims['azp'] = $this->client->getPublicId()->getValue();

        return $claims;
    }

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

    private function tryToEncrypt(Client $client, string $jwt): string
    {
        $clientKeySet = $this->getClientKeySet($client);
        $keyEncryptionAlgorithm = $this->jweBuilder->getKeyEncryptionAlgorithmManager()->get($this->keyEncryptionAlgorithm);
        $encryptionKey = $clientKeySet->selectKey('enc', $keyEncryptionAlgorithm);
        if (null === $encryptionKey) {
            throw new \InvalidArgumentException('No encryption key available for the client.');
        }
        $header = [
            'typ' => 'JWT',
            'jti' => Base64Url::encode(\random_bytes(16)),
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

    private function getSignatureKey(string $signatureAlgorithm): JWK
    {
        $keys = $this->signatureKeys;
        if ($this->client->has('client_secret')) {
            $jwk = JWK::create([
                'kty' => 'oct',
                'use' => 'sig',
                'k' => Base64Url::encode($this->client->get('client_secret')),
            ]);
            $keys = $keys->with($jwk);
        }
        $signatureAlgorithm = $this->jwsBuilder->getSignatureAlgorithmManager()->get($signatureAlgorithm);
        if ('none' === $signatureAlgorithm->name()) {
            return JWK::create(['kty' => 'none', 'alg' => 'none', 'use' => 'sig']);
        }
        $signatureKey = $keys->selectKey('sig', $signatureAlgorithm);
        if (null === $signatureKey) {
            throw new \InvalidArgumentException('Unable to find a key to sign the ID Token. Please verify the selected key set contains suitable keys.');
        }

        return $signatureKey;
    }

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

    private function getHash(TokenId $tokenId): string
    {
        return Base64Url::encode(\mb_substr(\hash($this->getHashMethod(), $tokenId->getValue(), true), 0, $this->getHashSize(), '8bit'));
    }

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

        if (!\array_key_exists($this->signatureAlgorithm, $map)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }

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

        if (!\array_key_exists($this->signatureAlgorithm, $map)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Algorithm "%s" is not supported', $this->signatureAlgorithm));
        }

        return $map[$this->signatureAlgorithm];
    }

    private function getClientKeySet(Client $client): JWKSet
    {
        $keyset = JWKSet::createFromKeys([]);
        if ($client->has('jwks')) {
            $jwks = JWKSet::createFromJson($client->get('jwks'));
            foreach ($jwks as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }
        if ($client->has('client_secret')) {
            $jwk = JWK::create([
                'kty' => 'oct',
                'use' => 'enc',
                'k' => Base64Url::encode($client->get('client_secret')),
            ]);
            $keyset = $keyset->with($jwk);
        }
        if ($client->has('jwks_uri') && null !== $this->jkuFactory) {
            $jwksUri = $this->jkuFactory->loadFromUrl($client->get('jwks_uri'));
            foreach ($jwksUri as $jwk) {
                $keyset = $keyset->with($jwk);
            }
        }

        if (empty($keyset)) {
            throw new \InvalidArgumentException('The client has no key or key set.');
        }

        return $keyset;
    }
}
