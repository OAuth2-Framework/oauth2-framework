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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
     * @var bool
     */
    private $isRequired;

    /**
     * InitialAccessTokenMiddleware constructor.
     */
    public function __construct(BearerToken $bearerToken, InitialAccessTokenRepository $initialAccessTokenRepository, bool $isRequired)
    {
        $this->bearerToken = $bearerToken;
        $this->initialAccessTokenRepository = $initialAccessTokenRepository;
        $this->isRequired = $isRequired;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $values = [];
            $token = $this->bearerToken->find($request, $values);
            if (null === $token) {
                if (!$this->isRequired) {
                    return $handler->handle($request);
                }

                throw new \InvalidArgumentException('Initial Access Token is missing or invalid.');
            }

            $initialAccessToken = $this->initialAccessTokenRepository->find(new InitialAccessTokenId($token));

            if (null === $initialAccessToken || $initialAccessToken->isRevoked()) {
                throw new \InvalidArgumentException('Initial Access Token is missing or invalid.');
            }
            if ($initialAccessToken->hasExpired()) {
                throw new \InvalidArgumentException('Initial Access Token expired.');
            }

            $request = $request->withAttribute('initial_access_token', $initialAccessToken);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }

        return $handler->handle($request);
    }
}
