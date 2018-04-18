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
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class OAuth2MessageMiddleware implements MiddlewareInterface
{
    /**
     * @var OAuth2MessageFactoryManager
     */
    private $auth2messageFactoryManager;

    /**
     * OAuth2ResponseMiddleware constructor.
     *
     * @param OAuth2MessageFactoryManager $auth2messageFactoryManager
     */
    public function __construct(OAuth2MessageFactoryManager $auth2messageFactoryManager)
    {
        $this->auth2messageFactoryManager = $auth2messageFactoryManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2Message $e) {
            return $oauth2Response = $this->auth2messageFactoryManager->getResponse($e);
        }
    }
}
