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

use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class SelectAccountEndpoint extends AbstractEndpoint
{
    /**
     * @var SelectAccountHandler
     */
    private $selectAccountHandler;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session, SelectAccountHandler $selectAccountHandler)
    {
        parent::__construct($responseFactory, $session);
        $this->selectAccountHandler = $selectAccountHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationId = $this->getAuthorizationId($request);
        $authorization = $this->getAuthorization($authorizationId);
        try {
            $this->selectAccountHandler->prepare($request, $authorizationId, $authorization);
            if ($this->selectAccountHandler->hasBeenProcessed($request, $authorizationId, $authorization)) {
                if (!$this->selectAccountHandler->isValid($request, $authorizationId, $authorization)) {
                    throw new OAuth2AuthorizationException(OAuth2Error::ERROR_ACCOUNT_SELECTION_REQUIRED, 'The resource owner account selection failed.', $authorization);
                }
                //$isAuthenticationNeeded = $this->userCheckerManager->isAuthenticationNeeded($authorization);
                //$isConsentNeeded = !$this->consentRepository || !$this->consentRepository->hasConsentBeenGiven($authorization);

                switch (true) {
                    case $authorization->hasPrompt('login') || $isAuthenticationNeeded:
                        $routeName = 'oauth2_server_login_endpoint';
                        break;
                    case $authorization->hasPrompt('consent') || $isConsentNeeded:
                    default:
                        $routeName = 'oauth2_server_consent_endpoint';
                        break;
                }

                $redirectTo = $this->getRouteFor($routeName, $authorizationId);

                return $this->createRedirectResponse($redirectTo);
            }

            return $this->selectAccountHandler->process($request, $authorizationId, $authorization);
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorization);
        } catch (\Throwable $e) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization);
        }
    }

    abstract protected function getRouteFor(string $action, string $authorizationId): string;
}
