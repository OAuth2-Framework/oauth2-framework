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

namespace OAuth2Framework\Component\Server\GrantType;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Command\AuthCode\MarkAuthCodeAsUsedCommand;
use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\GrantType\PKCEMethod\PKCEMethodManager;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCode;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

final class AuthorizationCodeGrantType implements GrantTypeInterface
{
    /**
     * @var AuthCodeRepositoryInterface
     */
    private $authCodeRepository;

    /**
     * @var PKCEMethodManager
     */
    private $pkceMethodManager;

    /**
     * @var MessageBus
     */
    private $commandBus;

    /**
     * AuthorizationCodeGrantType constructor.
     *
     * @param AuthCodeRepositoryInterface $authCodeRepository
     * @param PKCEMethodManager           $pkceMethodManager
     * @param MessageBus                  $commandBus
     */
    public function __construct(AuthCodeRepositoryInterface $authCodeRepository, PKCEMethodManager $pkceMethodManager, MessageBus $commandBus)
    {
        $this->authCodeRepository = $authCodeRepository;
        $this->pkceMethodManager = $pkceMethodManager;
        $this->commandBus = $commandBus;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedResponseTypes(): array
    {
        return ['code'];
    }

    /**
     * {@inheritdoc}
     */
    public function getGrantType(): string
    {
        return 'authorization_code';
    }

    /**
     * {@inheritdoc}
     */
    public function checkTokenRequest(ServerRequestInterface $request)
    {
        $parameters = $request->getParsedBody() ?? [];
        $requiredParameters = ['code', 'redirect_uri'];

        foreach ($requiredParameters as $requiredParameter) {
            if (!array_key_exists($requiredParameter, $parameters)) {
                throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => sprintf('The parameter \'%s\' is missing.', $requiredParameter)]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepareTokenResponse(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $authCode = $this->getAuthCode($parameters['code']);

        if (true === $authCode->isUsed() || true === $authCode->isRevoked()) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT, 'error_description' => 'The parameter \'code\' is invalid.']);
        }
        $grantTypeResponse = $grantTypeResponse->withAvailableScopes($authCode->getScopes());

        //Nothing to do
        return $grantTypeResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function grant(ServerRequestInterface $request, GrantTypeData $grantTypeResponse): GrantTypeData
    {
        $parameters = $request->getParsedBody() ?? [];
        $authCode = $this->getAuthCode($parameters['code']);
        $this->checkClient($grantTypeResponse->getClient(), $parameters);

        $this->checkAuthCode($authCode, $grantTypeResponse->getClient());
        $this->checkPKCE($authCode, $parameters);

        $redirectUri = $parameters['redirect_uri'];

        // Validate the redirect URI.
        $this->checkRedirectUri($authCode, $redirectUri);
        $grantTypeResponse = $grantTypeResponse->withMetadata('redirect_uri', $redirectUri);

        $grantTypeResponse = $grantTypeResponse->withMetadata('code', $authCode);
        $grantTypeResponse = $grantTypeResponse->withResourceOwnerId($authCode->getResourceOwnerId());

        // Refresh Token
        if ($authCode->isRefreshTokenIssued()) {
            $grantTypeResponse = $grantTypeResponse->withRefreshToken();
        } else {
            $grantTypeResponse = $grantTypeResponse->withoutRefreshToken();
        }

        $authCodeUsedCommand = MarkAuthCodeAsUsedCommand::create($authCode->getTokenId());
        $this->commandBus->handle($authCodeUsedCommand);

        return $grantTypeResponse;
    }

    /**
     * @param string $code
     *
     * @throws OAuth2Exception
     *
     * @return AuthCode
     */
    private function getAuthCode(string $code)
    {
        $authCode = $this->authCodeRepository->find(AuthCodeId::create($code));

        if (!$authCode instanceof AuthCode) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT,
                    'error_description' => 'Code does not exist or is invalid for the client.',
                ]
            );
        }

        return $authCode;
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
                throw new OAuth2Exception(
                    400,
                    [
                        'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                        'error_description' => 'The \'client_id\' parameter is required for non-confidential clients.',
                    ]
                );
            }
        }
    }

    /**
     * @param \OAuth2Framework\Component\Server\Model\AuthCode\AuthCode $authCode
     * @param array                                                     $parameters
     *
     * @throws OAuth2Exception
     */
    private function checkPKCE(AuthCode $authCode, array $parameters)
    {
        $params = $authCode->getQueryParams();
        if (!array_key_exists('code_challenge', $params)) {
            return;
        }

        $code_challenge = $params['code_challenge'];
        $code_challenge_method = array_key_exists('code_challenge_method', $params) ? $params['code_challenge_method'] : 'plain';

        try {
            Assertion::keyExists($parameters, 'code_verifier', 'The parameter \'code_verifier\' is missing.');
            $code_verifier = $parameters['code_verifier'];
            $method = $this->pkceMethodManager->get($code_challenge_method);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => $e->getMessage(),
                ]
            );
        }

        if (false === $method->isChallengeVerified($code_verifier, $code_challenge)) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT,
                    'error_description' => 'The parameter \'code_verifier\' is invalid.',
                ]
            );
        }
    }

    /**
     * @param \OAuth2Framework\Component\Server\Model\AuthCode\AuthCode $authCode
     * @param string                                                    $redirectUri
     *
     * @throws OAuth2Exception
     */
    private function checkRedirectUri(AuthCode $authCode, string $redirectUri)
    {
        if ($redirectUri !== $authCode->getRedirectUri()) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => 'The parameter \'redirect_uri\' is invalid.',
                ]
            );
        }
    }

    /**
     * @param \OAuth2Framework\Component\Server\Model\AuthCode\AuthCode $authCode
     * @param Client                                                    $client
     *
     * @throws OAuth2Exception
     */
    private function checkAuthCode(AuthCode $authCode, Client $client)
    {
        if ($client->getPublicId()->getValue() !== $authCode->getClientId()->getValue()) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT,
                    'error_description' => 'Code does not exist or is invalid for the client.',
                ]
            );
        }

        if ($authCode->hasExpired()) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_GRANT,
                    'error_description' => 'The authorization code has expired.',
                ]
            );
        }
    }
}
