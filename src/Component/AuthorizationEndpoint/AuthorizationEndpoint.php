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
use Http\Message\MessageFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AuthorizationEndpoint extends AbstractEndpoint
{
    private $authorizationRequestLoader;

    private $parameterCheckerManager;

    private $userAccountDiscovery;

    private $userAccountCheckerManager;

    private $router;

    private $consentRepository;

    public function __construct(MessageFactory $messageFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, UserAccountCheckerManager $userAccountCheckerManager, SessionInterface $session, RouterInterface $router, ConsentRepository $consentRepository)
    {
        parent::__construct($messageFactory, $session);
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->userAccountDiscovery = $userAccountDiscovery;
        $this->userAccountCheckerManager = $userAccountCheckerManager;
        $this->router = $router;
        $this->consentRepository = $consentRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorization = $this->authorizationRequestLoader->load($request);
            $authorization = $this->parameterCheckerManager->process($authorization);
            $userAccount = $this->userAccountDiscovery->find();

            if (null !== $userAccount) {
                $isFullyAuthenticated = $this->userAccountDiscovery->isFullyAuthenticated();
                $authorization->setUserAccount($userAccount, $isFullyAuthenticated);
                $this->userAccountCheckerManager->check($authorization);

                switch (true) {
                    case $authorization->hasPrompt('none'):
                        if (!$this->consentRepository->hasConsentBeenGiven($authorization)) {
                            throw $this->buildOAuth2Error($authorization, OAuth2Error::ERROR_INTERACTION_REQUIRED, 'The resource owner consent is required.');
                        }
                        $authorization->allow();
                        $routeName = 'authorization_process_endpoint';
                        break;
                    case $authorization->hasPrompt('login'):
                        $routeName = 'authorization_login_endpoint';
                        break;
                    case $authorization->hasPrompt('select_account'):
                        $routeName = 'authorization_select_account_endpoint';
                        break;
                    case $authorization->hasPrompt('consent'):
                    default:
                        $routeName = 'authorization_consent_endpoint';
                        break;
                }

                $authorizationId = Base64Url::encode(random_bytes(64));
                $authorizationId = $this->saveAuthorization($authorizationId, $authorization);
                $redirectTo = $this->router->generate($routeName, ['authorization_id' => $authorizationId]);

                return $this->createRedirectResponse($redirectTo);
            } else {
                if ($authorization->hasPrompt('none')) {
                    if (!$this->consentRepository->hasConsentBeenGiven($authorization)) {
                        throw $this->buildOAuth2Error($authorization, OAuth2Error::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.');
                    }
                    $authorization->allow();
                    $routeName = 'authorization_process_endpoint';
                } else {
                    $routeName = 'authorization_login_endpoint';
                }

                $authorizationId = Base64Url::encode(random_bytes(64));
                $authorizationId = $this->saveAuthorization($authorizationId, $authorization);
                $redirectTo = $this->router->generate($routeName, ['authorization_id' => $authorizationId]);

                return $this->createRedirectResponse($redirectTo);
            }
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, null);
        }
    }
}
