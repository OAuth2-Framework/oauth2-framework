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

namespace OAuth2Framework\Component\Server\Middleware;

use Assert\Assertion;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenType\BearerToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InitialAccessTokenMiddleware implements MiddlewareInterface
{
    /**
     * @var \OAuth2Framework\Component\Server\TokenType\BearerToken
     */
    private $bearerToken;

    /**
     * @var InitialAccessTokenRepositoryInterface
     */
    private $initialAccessTokenRepository;

    /**
     * InitialAccessTokenMiddleware constructor.
     *
     * @param BearerToken                           $bearerToken
     * @param InitialAccessTokenRepositoryInterface $initialAccessTokenRepository
     */
    public function __construct(BearerToken $bearerToken, InitialAccessTokenRepositoryInterface $initialAccessTokenRepository)
    {
        $this->bearerToken = $bearerToken;
        $this->initialAccessTokenRepository = $initialAccessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        try {
            $values = [];
            $token = $this->bearerToken->findToken($request, $values);
            Assertion::notNull($token, 'Initial Access Token is missing or invalid.');

            $initialAccessToken = $this->initialAccessTokenRepository->find(InitialAccessTokenId::create($token));
            Assertion::notNull($initialAccessToken, 'Initial Access Token is missing or invalid.');
            Assertion::false($initialAccessToken->hasExpired(), 'Initial Access Token expired.');
            Assertion::false($initialAccessToken->isRevoked(), 'Initial Access Token is missing or invalid.');

            $request = $request->withAttribute('initial_access_token', $initialAccessToken);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, 'error_description' => $e->getMessage()]);
        }

        return $delegate->process($request);
    }
}
