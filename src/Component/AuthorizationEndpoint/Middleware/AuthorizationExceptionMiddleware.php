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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Middleware;

use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthorizationExceptionMiddleware implements MiddlewareInterface
{
    /**
     * @throws OAuth2Message
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2AuthorizationException $e) {
            $redirectUri = $e->getAuthorization()->getRedirectUri();
            $responseMode = $e->getAuthorization()->getResponseMode();
            if (null !== $redirectUri && null !== $responseMode) {
                throw new OAuth2Message(
                    303,
                    $e->getMessage(),
                    $e->getErrorDescription(),
                    [
                        'response_mode' => $responseMode,
                        'redirect_uri' => $redirectUri,
                    ],
                    $e
                );
            }
            if (null !== $redirectUri) {
                throw new OAuth2Message(
                    303,
                    $e->getMessage(),
                    $e->getErrorDescription(),
                    [
                        'response_mode' => new QueryResponseMode(),
                        'redirect_uri' => $redirectUri,
                    ],
                    $e
                );
            } else {
                throw new OAuth2Message(
                    400,
                    $e->getMessage(),
                    $e->getErrorDescription(),
                    [],
                    $e
                );
            }
        }
    }
}
