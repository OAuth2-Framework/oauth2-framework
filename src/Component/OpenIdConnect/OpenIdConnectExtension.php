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

use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class OpenIdConnectExtension implements TokenEndpointExtension
{
    /**
     * @var JWKSet|null
     */
    private $signatureKeys = null;

    /**
     * @var JWSBuilder|null
     */
    private $jwsBuilder = null;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder = null;

    /**
     * @var IdTokenBuilderFactory
     */
    private $idTokenBuilderFactory;

    /**
     * @var string
     */
    private $defaultSignatureAlgorithm;

    /**
     * OpenIdConnectExtension constructor.
     *
     * @param IdTokenBuilderFactory $idTokenBuilderFactory
     * @param string                $defaultSignatureAlgorithm
     * @param JWSBuilder            $jwsBuilder
     * @param JWKSet                $signatureKeys
     */
    public function __construct(IdTokenBuilderFactory $idTokenBuilderFactory, string $defaultSignatureAlgorithm, JWSBuilder $jwsBuilder, JWKSet $signatureKeys)
    {
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
        $this->idTokenBuilderFactory = $idTokenBuilderFactory;
        $this->defaultSignatureAlgorithm = $defaultSignatureAlgorithm;
    }

    /**
     * @param JWEBuilder $jweBuilder
     */
    public function enableEncryption(JWEBuilder $jweBuilder)
    {
        $this->jweBuilder = $jweBuilder;
    }

    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        return $next($request, $grantTypeData, $grantType);
    }

    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        $data = $next($client, $resourceOwner, $accessToken);
        if ($resourceOwner instanceof UserAccount && $this->hasOpenIdScope($accessToken) && $accessToken->getMetadata()->has('redirect_uri')) {
            $idToken = $this->issueIdToken($client, $resourceOwner, $accessToken);
            $data['id_token'] = $idToken;
        }

        return $data;
    }

    /**
     * @param Client      $client
     * @param UserAccount $userAccount
     * @param AccessToken $accessToken
     *
     * @return string
     */
    private function issueIdToken(Client $client, UserAccount $userAccount, AccessToken $accessToken): string
    {
        $redirectUri = $accessToken->getMetadata()->get('redirect_uri');
        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder($client, $userAccount, $redirectUri);

        $requestedClaims = $this->getIdTokenClaims($accessToken);
        $idTokenBuilder = $idTokenBuilder->withRequestedClaims($requestedClaims);

        $idTokenBuilder = $idTokenBuilder->withAccessTokenId($accessToken->getAccessTokenId());

        if ($client->has('id_token_signed_response_alg')) {
            $signatureAlgorithm = $client->get('id_token_signed_response_alg');
            $idTokenBuilder = $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        } else {
            $idTokenBuilder = $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $this->defaultSignatureAlgorithm);
        }
        if ($client->has('userinfo_encrypted_response_alg') && $client->has('userinfo_encrypted_response_enc') && null !== $this->jweBuilder) {
            $keyEncryptionAlgorithm = $client->get('userinfo_encrypted_response_alg');
            $contentEncryptionAlgorithm = $client->get('userinfo_encrypted_response_enc');
            $idTokenBuilder = $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }
        if ($client->has('require_auth_time')) {
            $idTokenBuilder->withAuthenticationTime();
        }
        $idTokenBuilder = $idTokenBuilder->withAccessToken($accessToken);

        return $idTokenBuilder->build();
    }

    /**
     * @param AccessToken $accessToken
     *
     * @return array
     */
    private function getIdTokenClaims(AccessToken $accessToken): array
    {
        if (!$accessToken->getMetadata()->has('requested_claims')) {
            return [];
        }

        $requestedClaims = $accessToken->getMetadata()->get('requested_claims');
        $requestedClaims = \json_decode($requestedClaims, true);
        if (!\is_array($requestedClaims)) {
            throw new \InvalidArgumentException('Invalid claim request');
        }
        if (true === \array_key_exists('id_token', $requestedClaims)) {
            return $requestedClaims['id_token'];
        }

        return [];
    }

    private function hasOpenIdScope(AccessToken $accessToken): bool
    {
        return $accessToken->getParameter()->has('scope') && \in_array('openid', \explode(' ', $accessToken->getParameter()->get('scope')), true);
    }
}
