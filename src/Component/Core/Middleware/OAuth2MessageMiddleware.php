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

namespace OAuth2Framework\Component\Core\Middleware;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class OAuth2MessageMiddleware implements MiddlewareInterface
{
    private $auth2messageFactoryManager;

    public function __construct(OAuth2MessageFactoryManager $auth2messageFactoryManager)
    {
        $this->auth2messageFactoryManager = $auth2messageFactoryManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2Error $e) {
            return $oauth2Response = $this->auth2messageFactoryManager->getResponse($e);
        }
    }
}
