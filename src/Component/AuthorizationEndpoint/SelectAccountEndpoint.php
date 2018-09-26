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
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class SelectAccountEndpoint extends AbstractEndpoint
{
    private $selectAccountHandler;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session, SelectAccountHandler $selectAccountHandler)
    {
        parent::__construct($responseFactory, $session);
        $this->selectAccountHandler = $selectAccountHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorizationId = $this->getAuthorizationId($request);
            $authorization = $this->getAuthorization($authorizationId);
            $this->selectAccountHandler->prepare($request, $authorizationId, $authorization);
            if ($this->selectAccountHandler->hasBeenProcessed($request, $authorizationId, $authorization)) {
                if (!$this->selectAccountHandler->isValid($request, $authorizationId, $authorization)) {
                    throw $this->buildOAuth2Error($authorization, OAuth2Error::ERROR_ACCOUNT_SELECTION_REQUIRED, 'The resource owner account selection failed.');
                }

                switch (true) {
                    case $authorization->hasPrompt('consent'):
                    default:
                        $routeName = 'authorization_consent_endpoint';
                        break;
                }
                $redirectTo = $this->getRouteFor($routeName, $authorizationId);

                return $this->createRedirectResponse($redirectTo);
            }

            return $this->selectAccountHandler->process($request, $authorizationId, $authorization);
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, null);
        }
    }

    abstract protected function getRouteFor(string $action, string $authorizationId): string;
}
