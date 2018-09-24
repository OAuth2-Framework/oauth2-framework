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

namespace OAuth2Framework\Component\OpenIdConnect\IdTokenGrant;

use Jose\Component\Core\JWKSet;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Signature\JWSBuilder;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;

final class IdTokenResponseType implements ResponseType
{
    /**
     * @var JWKSet
     */
    private $signatureKeys;

    /**
     * @var JWSBuilder
     */
    private $jwsBuilder;

    /**
     * @var JWEBuilder|null
     */
    private $jweBuilder;

    /**
     * @var IdTokenBuilderFactory
     */
    private $idTokenBuilderFactory;

    /**
     * @var string
     */
    private $defaultSignatureAlgorithm;

    /**
     * IdTokenResponseType constructor.
     */
    public function __construct(IdTokenBuilderFactory $idTokenBuilderFactory, string $defaultSignatureAlgorithm, JWSBuilder $jwsBuilder, JWKSet $signatureKeys, ?JWEBuilder $jweBuilder)
    {
        if ('none' === $defaultSignatureAlgorithm) {
            throw new \InvalidArgumentException('The algorithm "none" is not allowed for ID Tokens issued through the authorization endpoint.');
        }
        $this->idTokenBuilderFactory = $idTokenBuilderFactory;
        $this->defaultSignatureAlgorithm = $defaultSignatureAlgorithm;
        $this->jwsBuilder = $jwsBuilder;
        $this->signatureKeys = $signatureKeys;
        $this->jweBuilder = $jweBuilder;
    }

    public function associatedGrantTypes(): array
    {
        return [];
    }

    public function name(): string
    {
        return 'id_token';
    }

    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function preProcess(AuthorizationRequest $authorization): AuthorizationRequest
    {
        return $authorization;
    }

    public function process(AuthorizationRequest $authorization): AuthorizationRequest
    {
        if ($authorization->hasQueryParam('scope') && \in_array('openid', \explode(' ', $authorization->getQueryParam('scope')), true)) {
            if (!\array_key_exists('nonce', $authorization->getQueryParams())) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, 'The parameter "nonce" is mandatory using "id_token" response type.');
            }

            $authorization = $this->populateWithIdToken($authorization);
        }

        return $authorization;
    }

    private function populateWithIdToken(AuthorizationRequest $authorization): AuthorizationRequest
    {
        $params = $authorization->getQueryParams();
        $requestedClaims = $this->getIdTokenClaims($authorization);
        if ($authorization->hasQueryParam('claims')) {
            //$authorization->withMetadata('requested_claims', $authorization->getQueryParam('claims'));
        }

        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder(
            $authorization->getClient(),
            $authorization->getUserAccount(),
            $authorization->getRedirectUri()
        );
        $idTokenBuilder->withRequestedClaims($requestedClaims);
        $idTokenBuilder->withScope($authorization->getQueryParam('scope'));
        $idTokenBuilder->withNonce($params['nonce']);

        if ($authorization->hasResponseParameter('code')) {
            $idTokenBuilder->withAuthorizationCodeId(new AuthorizationCodeId($authorization->getResponseParameter('code')));
        }

        if ($authorization->hasResponseParameter('access_token')) {
            $idTokenBuilder->withAccessTokenId(new AccessTokenId($authorization->getResponseParameter('access_token')));
        }

        if ($authorization->hasQueryParam('claims_locales')) {
            $idTokenBuilder->withClaimsLocales($authorization->getQueryParam('claims_locales'));
        }

        if ($authorization->hasResponseParameter('expires_in')) {
            $idTokenBuilder->withExpirationAt(new \DateTimeImmutable(\sprintf('now +%s sec', $authorization->getResponseParameter('expires_in'))));
        }

        if ($authorization->hasQueryParam('max_age')) {
            $idTokenBuilder->withAuthenticationTime();
        }

        if ($authorization->getClient()->has('id_token_signed_response_alg')) {
            $signatureAlgorithm = $authorization->getClient()->get('id_token_signed_response_alg');
            if ('none' === $signatureAlgorithm) {
                throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_CLIENT, 'The ID Token signature algorithm set for the client (parameter "id_token_signed_response_alg") is "none" but this algorithm is not allowed for ID Tokens issued through the authorization endpoint.');
            }
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $signatureAlgorithm);
        } else {
            $idTokenBuilder->withSignature($this->jwsBuilder, $this->signatureKeys, $this->defaultSignatureAlgorithm);
        }
        if ($authorization->getClient()->has('id_token_encrypted_response_alg') && $authorization->getClient()->has('id_token_encrypted_response_enc') && null !== $this->jweBuilder) {
            $keyEncryptionAlgorithm = $authorization->getClient()->get('id_token_encrypted_response_alg');
            $contentEncryptionAlgorithm = $authorization->getClient()->get('id_token_encrypted_response_enc');
            $idTokenBuilder->withEncryption($this->jweBuilder, $keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }

        $idToken = $idTokenBuilder->build();
        $authorization->setResponseParameter('id_token', $idToken);

        return $authorization;
    }

    private function getIdTokenClaims(AuthorizationRequest $authorization): array
    {
        if (!$authorization->hasQueryParam('claims')) {
            return [];
        }

        $requestedClaims = $authorization->getQueryParam('claims');
        $requestedClaims = \json_decode($requestedClaims, true);
        if (!\is_array($requestedClaims)) {
            throw new \InvalidArgumentException('Invalid claim request');
        }
        if (true === \array_key_exists('id_token', $requestedClaims)) {
            return $requestedClaims['id_token'];
        }

        return [];
    }
}
