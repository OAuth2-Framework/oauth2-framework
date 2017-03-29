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

namespace OAuth2Framework\Component\Server\ResponseType;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

final class IdTokenResponseType implements ResponseTypeInterface
{
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
     *
     * @param IdTokenBuilderFactory $idTokenBuilderFactory
     * @param string                $defaultSignatureAlgorithm
     */
    public function __construct(IdTokenBuilderFactory $idTokenBuilderFactory, string $defaultSignatureAlgorithm)
    {
        Assertion::notEq($defaultSignatureAlgorithm, 'none', 'The algorithm \'none\' is not allowed for ID Tokens issued through the authorization endpoint.');
        $this->idTokenBuilderFactory = $idTokenBuilderFactory;
        $this->defaultSignatureAlgorithm = $defaultSignatureAlgorithm;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedGrantTypes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseType(): string
    {
        return 'id_token';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseMode(): string
    {
        return self::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        $authorization = $next($authorization);

        if (in_array('openid', $authorization->getScopes())) {
            if (!array_key_exists('nonce', $authorization->getQueryParams())) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => 'The parameter \'nonce\' is mandatory using \'id_token\' response type.']);
            }

            $authorization = $this->populateWithIdToken($authorization);
        }

        return $authorization;
    }

    /**
     * @param Authorization $authorization
     *
     * @throws OAuth2Exception
     *
     * @return Authorization
     */
    private function populateWithIdToken(Authorization $authorization): Authorization
    {
        $params = $authorization->getQueryParams();
        $requestedClaims = $this->getIdTokenClaims($authorization);

        $idTokenBuilder = $this->idTokenBuilderFactory->createBuilder(
            $authorization->getClient(),
            $authorization->getUserAccount(),
            $authorization->getRedirectUri()
        );
        $idTokenBuilder = $idTokenBuilder->withRequestedClaims($requestedClaims);
        $idTokenBuilder = $idTokenBuilder->withScope($authorization->getScopes());
        $idTokenBuilder = $idTokenBuilder->withNonce($params['nonce']);

        if ($authorization->hasResponseParameter('code')) {
            $idTokenBuilder = $idTokenBuilder->withAuthCodeId(AuthCodeId::create($authorization->getResponseParameter('code')));
        }

        if ($authorization->hasResponseParameter('access_token')) {
            $idTokenBuilder = $idTokenBuilder->withAccessTokenId(AccessTokenId::create($authorization->getResponseParameter('access_token')));
        }

        if ($authorization->hasQueryParam('claims_locales')) {
            $idTokenBuilder = $idTokenBuilder->withClaimsLocales($authorization->getQueryParam('claims_locales'));
        }

        if ($authorization->hasResponseParameter('expires_in')) {
            $idTokenBuilder = $idTokenBuilder->withExpirationAt(new \DateTimeImmutable(sprintf('now +%s sec', $authorization->getResponseParameter('expires_in'))));
        }

        if ($authorization->hasQueryParam('max_age')) {
            $idTokenBuilder = $idTokenBuilder->withAuthenticationTime();
        }

        if ($authorization->getClient()->has('id_token_signed_response_alg')) {
            $signatureAlgorithm = $authorization->getClient()->get('id_token_signed_response_alg');
            if ('none' === $signatureAlgorithm) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT, 'error_description' => 'The ID Token signature algorithm set for the client (parameter \'id_token_signed_response_alg\') is \'none\' but this algorithm is not allowed for ID Tokens issued through the authorization endpoint.']);
            }
            $idTokenBuilder = $idTokenBuilder->withSignatureAlgorithm($signatureAlgorithm);
        } else {
            $idTokenBuilder = $idTokenBuilder->withSignatureAlgorithm($this->defaultSignatureAlgorithm);
        }
        if ($authorization->getClient()->has('id_token_encrypted_response_alg') && $authorization->getClient()->has('id_token_encrypted_response_enc')) {
            $keyEncryptionAlgorithm = $authorization->getClient()->get('id_token_encrypted_response_alg');
            $contentEncryptionAlgorithm = $authorization->getClient()->get('id_token_encrypted_response_enc');
            $idTokenBuilder = $idTokenBuilder->withEncryptionAlgorithms($keyEncryptionAlgorithm, $contentEncryptionAlgorithm);
        }

        $idToken = $idTokenBuilder->build();

        return $authorization->withResponseParameter('id_token', $idToken);
    }

    /**
     * @param Authorization $authorization
     *
     * @return array
     */
    private function getIdTokenClaims(Authorization $authorization): array
    {
        if (!$authorization->hasQueryParam('claims')) {
            return [];
        }

        $requestedClaims = $authorization->getQueryParam('claims');
        if (true === array_key_exists('id_token', $requestedClaims)) {
            return $requestedClaims['id_token'];
        }

        return [];
    }
}
