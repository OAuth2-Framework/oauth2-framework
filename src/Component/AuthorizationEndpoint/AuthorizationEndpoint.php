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

use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class AuthorizationEndpoint extends AbstractEndpoint
{
    /**
     * @var AuthorizationRequestLoader
     */
    private $authorizationRequestLoader;

    /**
     * @var ParameterCheckerManager
     */
    private $parameterCheckerManager;

    /**
     * @var UserAccountDiscovery
     */
    private $userAccountDiscovery;

    /**
     * @var UserAuthenticationCheckerManager
     */
    private $userCheckerManager;

    /**
     * @var ConsentRepository|null
     */
    private $consentRepository;

    public function __construct(ResponseFactoryInterface $responseFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, UserAuthenticationCheckerManager $userCheckerManager, SessionInterface $session, ?ConsentRepository $consentRepository)
    {
        parent::__construct($responseFactory, $session);
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->userAccountDiscovery = $userAccountDiscovery;
        $this->userCheckerManager = $userCheckerManager;
        $this->consentRepository = $consentRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $this->loadAuthorization($request);

        try {
            $userAccount = $this->userAccountDiscovery->getCurrentAccount();

            if (null !== $userAccount) {
                return $this->processWithAuthenticatedUser($authorization, $userAccount);
            } else {
                return $this->processWithUnauthenticatedUser($authorization);
            }
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorization, $e);
        } catch (\Throwable $e) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }

    private function processWithAuthenticatedUser(AuthorizationRequest $authorization, UserAccount $userAccount): ResponseInterface
    {
        $authorization->setUserAccount($userAccount);
        $isAuthenticationNeeded = $this->userCheckerManager->isAuthenticationNeeded($authorization);
        $isConsentNeeded = null !== $this->consentRepository || !$this->consentRepository->hasConsentBeenGiven($authorization);

        switch (true) {
            case $authorization->hasPrompt('none'):
                if ($isConsentNeeded) {
                    throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INTERACTION_REQUIRED, 'The resource owner consent is required.', $authorization);
                }
                $authorization->allow();
                $routeName = 'oauth2_server_process_endpoint';
                break;
            case $authorization->hasPrompt('select_account'):
                $routeName = 'oauth2_server_select_account_endpoint';
                break;
            case $authorization->hasPrompt('login') || $isAuthenticationNeeded:
                $routeName = 'oauth2_server_login_endpoint';
                break;
            case $authorization->hasPrompt('consent') || $isConsentNeeded:
                $routeName = 'oauth2_server_consent_endpoint';
                break;
            default:
                $routeName = 'oauth2_server_consent_endpoint';
                break;
        }

        $authorizationId = Base64Url::encode(random_bytes(64));
        $this->saveAuthorization($authorizationId, $authorization);
        $redirectTo = $this->getRouteFor($routeName, $authorizationId);

        return $this->createRedirectResponse($redirectTo);
    }

    private function processWithUnauthenticatedUser(AuthorizationRequest $authorization): ResponseInterface
    {
        if ($authorization->hasPrompt('none')) {
            $isConsentNeeded = null !== $this->consentRepository || !$this->consentRepository->hasConsentBeenGiven($authorization);
            if ($isConsentNeeded) {
                throw new OAuth2AuthorizationException(OAuth2Error::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.', $authorization);
            }
            $authorization->allow();
            $routeName = 'oauth2_server_process_endpoint';
        } else {
            $routeName = 'oauth2_server_login_endpoint';
        }

        $authorizationId = Base64Url::encode(random_bytes(64));
        $this->saveAuthorization($authorizationId, $authorization);
        $redirectTo = $this->getRouteFor($routeName, $authorizationId);

        return $this->createRedirectResponse($redirectTo);
    }

    private function loadAuthorization(ServerRequestInterface $request): AuthorizationRequest
    {
        try {
            $authorization = $this->authorizationRequestLoader->load($request);
            $this->parameterCheckerManager->check($authorization);

            return $authorization;
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw OAuth2Error::invalidRequest($e->getMessage());
        }
    }

    abstract protected function getRouteFor(string $action, string $authorizationId): string;
}
