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
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class LoginEndpoint extends AbstractEndpoint
{
    /**
     * @var LoginHandler
     */
    private $loginHandler;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session, LoginHandler $loginHandler)
    {
        parent::__construct($responseFactory, $session);
        $this->loginHandler = $loginHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationId = $this->getAuthorizationId($request);
        $authorization = $this->getAuthorization($authorizationId);
        try {
            $this->loginHandler->prepare($request, $authorizationId, $authorization);
            if (!$this->loginHandler->hasBeenProcessed($request, $authorizationId, $authorization)) {
                if (!$this->loginHandler->isValid($request, $authorizationId, $authorization)) {
                    throw new OAuth2AuthorizationException(OAuth2Error::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.', $authorization);
                }

                $redirectTo = $this->getRouteFor('oauth2_server_consent_endpoint', $authorizationId);

                return $this->createRedirectResponse($redirectTo);
            }

            return $this->loginHandler->process($request, $authorizationId, $authorization);
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorization);
        } catch (\Throwable $e) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization);
        }
    }

    abstract protected function getRouteFor(string $action, string $authorizationId): string;
}
