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


use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InitialAccessTokenMiddleware implements MiddlewareInterface
{
    /**
     * @var BearerToken
     */
    private $bearerToken;

    /**
     * @var InitialAccessTokenRepository
     */
    private $initialAccessTokenRepository;

    /**
     * InitialAccessTokenMiddleware constructor.
     *
     * @param BearerToken                  $bearerToken
     * @param InitialAccessTokenRepository $initialAccessTokenRepository
     */
    public function __construct(BearerToken $bearerToken, InitialAccessTokenRepository $initialAccessTokenRepository)
    {
        $this->bearerToken = $bearerToken;
        $this->initialAccessTokenRepository = $initialAccessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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
            throw new OAuth2Exception(400, OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST, $e->getMessage());
        }

        return $handler->handle($request);
    }
}
