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
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use Psr\Http\Message\ServerRequestInterface;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;

final class AuthorizationCodeGrantType implements GrantType
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
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $requiredParameters = ['code', 'redirect_uri'];

        $diff = array_diff($requiredParameters, array_keys($parameters));
        if (!empty($diff)) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, sprintf('Missing grant type parameter(s): %s.', implode(', ', $diff)));
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
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);
        $authorizationCode = $this->getAuthorizationCode($parameters['code']);

        if (true === $authorizationCode->isUsed() || true === $authorizationCode->isRevoked()) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        $this->checkClient($grantTypeData->getClient(), $parameters);

        $this->checkAuthorizationCode($authorizationCode, $grantTypeData->getClient());
        $this->checkPKCE($authorizationCode, $parameters);

        $redirectUri = $parameters['redirect_uri'];

        // Validate the redirect URI.
        $this->checkRedirectUri($authorizationCode, $redirectUri);

        if ($authorizationCode->hasQueryParam('scope')) {
            $grantTypeData->withParameter('scope', $authorizationCode->getQueryParam('scope'));
        }
        foreach ($authorizationCode->getParameters() as $key => $parameter) {
            $grantTypeData->withParameter($key, $parameter);
        }

        $grantTypeData->getMetadata()->with('redirect_uri', $redirectUri);
        $grantTypeData->getMetadata()->with('authorization_code_id', $authorizationCode->getAuthorizationCodeId()->getValue());
        $grantTypeData->withResourceOwnerId($authorizationCode->getResourceOwnerId());
        $authorizationCode = $authorizationCode->markAsUsed();
        $this->authorizationCodeRepository->save($authorizationCode);

        return $grantTypeData;
    }

    /**
     * @param string $code
     *
     * @throws OAuth2Message
     *
     * @return AuthorizationCode
     */
    private function getAuthorizationCode(string $code)
    {
        $authorizationCode = $this->authorizationCodeRepository->find(AuthorizationCodeId::create($code));

        if (!$authorizationCode instanceof AuthorizationCode) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        return $authorizationCode;
    }

    /**
     * @param Client $client
     * @param array  $parameters
     *
     * @throws OAuth2Message
     */
    private function checkClient(Client $client, array $parameters)
    {
        if (true === $client->isPublic()) {
            if (!array_key_exists('client_id', $parameters) || $client->getPublicId()->getValue() !== $parameters['client_id']) {
                throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'The "client_id" parameter is required for non-confidential clients.');
            }
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param array             $parameters
     *
     * @throws OAuth2Message
     */
    private function checkPKCE(AuthorizationCode $authorizationCode, array $parameters)
    {
        $params = $authorizationCode->getQueryParams();
        if (!array_key_exists('code_challenge', $params)) {
            return;
        }

        $codeChallenge = $params['code_challenge'];
        $codeChallengeMethod = array_key_exists('code_challenge_method', $params) ? $params['code_challenge_method'] : 'plain';

        try {
            if (!array_key_exists('code_verifier', $parameters)) {
                throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The parameter "code_verifier" is missing or invalid.');
            }
            $code_verifier = $parameters['code_verifier'];
            $method = $this->pkceMethodManager->get($codeChallengeMethod);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }

        if (false === $method->isChallengeVerified($code_verifier, $codeChallenge)) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The parameter "code_verifier" is invalid or invalid.');
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param string            $redirectUri
     *
     * @throws OAuth2Message
     */
    private function checkRedirectUri(AuthorizationCode $authorizationCode, string $redirectUri)
    {
        if ($redirectUri !== $authorizationCode->getRedirectUri()) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'The parameter "redirect_uri" is invalid.');
        }
    }

    /**
     * @param AuthorizationCode $authorizationCode
     * @param Client            $client
     *
     * @throws OAuth2Message
     */
    private function checkAuthorizationCode(AuthorizationCode $authorizationCode, Client $client)
    {
        if ($client->getPublicId()->getValue() !== $authorizationCode->getClientId()->getValue()) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The parameter "code" is invalid.');
        }

        if ($authorizationCode->hasExpired()) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_GRANT, 'The authorization code expired.');
        }
    }
}
