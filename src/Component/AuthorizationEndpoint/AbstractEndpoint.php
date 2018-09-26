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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AbstractEndpoint implements MiddlewareInterface
{
    protected $session;

    protected $responseFactory;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session)
    {
        $this->responseFactory = $responseFactory;
        $this->session = $session;
    }

    protected function getAuthorizationId(ServerRequestInterface $request): string
    {
        $authorizationId = $request->getAttribute('authorization_id');
        if (null === $authorizationId) {
            throw new \InvalidArgumentException('Invalid authorization ID.');
        }

        return $authorizationId;
    }

    protected function saveAuthorization(string $authorizationId, AuthorizationRequest $authorization)
    {
        $this->session->set(sprintf('/authorization/%s', $authorizationId), $authorization);
    }

    protected function getAuthorization(string $authorizationId): AuthorizationRequest
    {
        $authorization = $this->session->get(sprintf('/authorization/%s', $authorizationId));
        if (null === $authorization) {
            throw new \InvalidArgumentException('Invalid authorization ID.');
        }

        return $authorization;
    }

    protected function buildOAuth2Error(AuthorizationRequest $authorization, string $error, string $errorDescription): OAuth2Error
    {
        $params = $authorization->getResponseParameters();
        if (null === $authorization->getResponseMode() || null === $authorization->getRedirectUri()) {
            throw new OAuth2Error(400, $error, $errorDescription);
        }
        $params += [
            'response_mode' => $authorization->getResponseMode(),
            'redirect_uri' => $authorization->getRedirectUri(),
        ];

        return new OAuth2Error(303, $error, $errorDescription, $params);
    }

    protected function createRedirectResponse(string $redirectTo): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(303);
        $response->withHeader('location', $redirectTo);

        return $response;
    }
}
