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

namespace OAuth2Framework\ServerBundle\Middleware;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenMiddleware implements MiddlewareInterface
{
    /**
     * @var TokenTypeManager
     */
    private $tokenTypeManager;

    /**
     * @var AccessTokenRepository
     */
    private $accessTokenRepository;

    /**
     * AccessTokenMiddleware constructor.
     *
     * @param TokenTypeManager      $tokenTypeManager
     * @param AccessTokenRepository $accessTokenRepository
     */
    public function __construct(TokenTypeManager $tokenTypeManager, AccessTokenRepository $accessTokenRepository)
    {
        $this->tokenTypeManager = $tokenTypeManager;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $additional_credential_values = [];
        $token = $this->tokenTypeManager->findToken($request, $additional_credential_values, $type);
        if (null !== $token) {
            $tokenId = AccessTokenId::create($token);
            $accessToken = $this->accessTokenRepository->find($tokenId);
            if (null === $accessToken || false === $type->isTokenRequestValid($accessToken, $request, $additional_credential_values)) {
                throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_TOKEN, 'Invalid access token.');
            }
            $request = $request->withAttribute('access_token', $accessToken);
        }

        return $handler->handle($request);
    }
}
