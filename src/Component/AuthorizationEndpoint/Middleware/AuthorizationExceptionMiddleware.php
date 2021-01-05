<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\Middleware;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\QueryResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseModeGuesser;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseTypeGuesser;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthorizationExceptionMiddleware implements MiddlewareInterface
{
    private ResponseTypeGuesser $responseTypeGuesser;
    private ResponseModeGuesser $responseModeGuesser;

    public function __construct(ResponseTypeGuesser $responseTypeGuesser, ResponseModeGuesser $responseModeGuesser)
    {
        $this->responseTypeGuesser = $responseTypeGuesser;
        $this->responseModeGuesser = $responseModeGuesser;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (OAuth2AuthorizationException $e) {
            $authorizationRequest = $e->getAuthorization();
            $data = $authorizationRequest->getResponseParameters();
            $responseType = $this->getResponseType($authorizationRequest);
            $responseMode = null === $responseType ? null : $this->getResponseMode($authorizationRequest, $responseType);

            switch (true) {
                case $authorizationRequest->hasQueryParam('redirect_uri') && null !== $responseMode:
                    $data += ['response_mode' => $responseMode, 'redirect_uri' => $authorizationRequest->getQueryParam('redirect_uri')];

                    throw new OAuth2Error(303, $e->getMessage(), $e->getErrorDescription(), $data, $e);
                case $authorizationRequest->hasQueryParam('redirect_uri'):
                    $data += ['response_mode' => new QueryResponseMode(), 'redirect_uri' => $authorizationRequest->getQueryParam('redirect_uri')];

                    throw new OAuth2Error(303, $e->getMessage(), $e->getErrorDescription(), $data, $e);
                default:
                    throw new OAuth2Error(400, $e->getMessage(), $e->getErrorDescription(), $data, $e);
            }
        }
    }

    private function getResponseType(AuthorizationRequest $authorizationRequest): ?ResponseType
    {
        try {
            return $this->responseTypeGuesser->get($authorizationRequest);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    private function getResponseMode(AuthorizationRequest $authorizationRequest, ResponseType $responseType): ?ResponseMode
    {
        try {
            return $this->responseModeGuesser->get($authorizationRequest, $responseType);
        } catch (Throwable $throwable) {
            return null;
        }
    }
}
