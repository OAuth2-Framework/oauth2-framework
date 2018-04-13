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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationCodeGrantType implements GrantType
{
    /**
     * @var AuthorizationCodeRepository
     */
    private $authorizationCodeRepository;

    /**
     * @var PKCEMethodManager
     */
    private $pkceMethodManager;

    /**
     * AuthorizationCodeGrantType constructor.
     *
     * @param AuthorizationCodeRepository $authorizationCodeRepository
     * @param PKCEMethodManager           $pkceMethodManager
     */
    public function __construct(AuthorizationCodeRepository $authorizationCodeRepository, PKCEMethodManager $pkceMethodManager)
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;
        $this->pkceMethodManager = $pkceMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    public function associatedResponseTypes(): array
    {
        return ['code'];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'authorization_code';
    }

    /**
     * {@inheritdoc}
     */
    public function checkRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['code', 'redirect_uri'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareResponse(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeData): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $authorizationCode = $this->getAuthorizationCode($parameters['code']);

        if (true === $authorizationCode->isUsed() || true === $authorizationCode->isRevoked()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        $this->checkClient($grantTypeData->getClient(), $parameters);

        $this->checkAuthorizationCode($authorizationCode, $grantTypeData->getClient());
        $this->checkPKCE($authorizationCode, $parameters);

        $redirectUri = $parameters['redirect_uri'];

        // Validate the redirect URI.
        $this->checkRedirectUri($authorizationCode, $redirectUri);

        foreach ($authorizationCode->getParameters() as $key => $parameter) {
            $grantTypeData = $grantTypeData->withParameter($key, $parameter);
        }

        $grantTypeData = $grantTypeData->withMetadata('redirect_uri', $redirectUri);

        $grantTypeData = $grantTypeData->withMetadata('code', $authorizationCode);
        $grantTypeData = $grantTypeData->withResourceOwnerId($authorizationCode->getResourceOwnerId());
        $authorizationCode = $authorizationCode->markAsUsed();
        $this->authorizationCodeRepository->save($authorizationCode);

        return $grantTypeData;
    }

    /**
     * @param string $code
     *
     * @throws OAuth2Exception
     *
     * @return AuthorizationCode
     */
    private function getAuthorizationCode(string $code)
    {
        $authorizationCode = $this->authorizationCodeRepository->find(AuthorizationCodeId::create($code));

        if (!$authorizationCode instanceof AuthorizationCode) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        return $authorizationCode;
    }

    /**
     * @param Client $client
     * @param array  $parameters
     *
     * @throws OAuth2Exception
     */
    private function checkClient(Client $client, array $parameters)
    {
        if (true === $client->isPublic()) {
            if (!array_key_exists('client_id', $parameters) || $client->getPublicId()->getValue() !== $parameters['client_id']) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'The "client_id" parameter is required for non-confidential clients.');
            }
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param array             $parameters
     *
     * @throws OAuth2Exception
     */
    private function checkPKCE(AuthorizationCode $authorizationCode, array $parameters)
    {
        $params = $authorizationCode->getQueryParams();
        if (!array_key_exists('code_challenge', $params)) {
            return;
        }

        $code_challenge = $params['code_challenge'];
        $code_challenge_method = array_key_exists('code_challenge_method', $params) ? $params['code_challenge_method'] : 'plain';

        try {
            if (!array_key_exists('code_verifier', $parameters)) {
                throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "code_verifier" is missing or invalid.');
            }
            $code_verifier = $parameters['code_verifier'];
            $method = $this->pkceMethodManager->get($code_challenge_method);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
        }

        if (false === $method->isChallengeVerified($code_verifier, $code_challenge)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "code_verifier" is invalid or invalid.');
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param string            $redirectUri
     *
     * @throws OAuth2Exception
     */
    private function checkRedirectUri(AuthorizationCode $authorizationCode, string $redirectUri)
    {
        if ($redirectUri !== $authorizationCode->getRedirectUri()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'The parameter "redirect_uri" is invalid.');
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param Client            $client
     *
     * @throws OAuth2Exception
     */
    private function checkAuthorizationCode(AuthorizationCode $authorizationCode, Client $client)
    {
        if ($client->getPublicId()->getValue() !== $authorizationCode->getClientId()->getValue()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        if ($authorizationCode->hasExpired()) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_GRANT, 'The authorization code expired.');
        }
    }
}
