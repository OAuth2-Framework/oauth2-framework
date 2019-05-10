<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Middleware;

use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthorizationExceptionMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2AuthorizationException $e) {
            $authorization = $e->getAuthorization();
            switch (true) {
                case $authorization->hasRedirectUri() && $authorization->hasResponseMode():
                    throw new OAuth2Error(303, $e->getMessage(), $e->getErrorDescription(), ['response_mode' => $authorization->getResponseMode(), 'redirect_uri' => $authorization->getRedirectUri()], $e);
                case $authorization->hasRedirectUri():
                    throw new OAuth2Error(303, $e->getMessage(), $e->getErrorDescription(), ['response_mode' => new QueryResponseMode(), 'redirect_uri' => $authorization->getRedirectUri()], $e);
                default:
                    throw new OAuth2Error(400, $e->getMessage(), $e->getErrorDescription(), [], $e);
            }
        }
    }
}
