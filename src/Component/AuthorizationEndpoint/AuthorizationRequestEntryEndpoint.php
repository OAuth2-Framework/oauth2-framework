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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationRequestEntryEndpoint
{
    private ParameterCheckerManager $parameterCheckerManager;

    private AuthorizationRequestLoader $authorizationRequestLoader;

    private AuthorizationRequestStorage $authorizationRequestStorage;

    private AuthorizationRequestHandler $authorizationRequestHandler;

    private UserAccountDiscovery $userAccountDiscovery;

    public function __construct(ParameterCheckerManager $parameterCheckerManager, AuthorizationRequestLoader $authorizationRequestLoader, AuthorizationRequestStorage $authorizationRequestStorage, AuthorizationRequestHandler $authorizationRequestHandler, UserAccountDiscovery $userAccountDiscovery)
    {
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->authorizationRequestStorage = $authorizationRequestStorage;
        $this->authorizationRequestHandler = $authorizationRequestHandler;
        $this->userAccountDiscovery = $userAccountDiscovery;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationRequest = $this->authorizationRequestLoader->load($request->getQueryParams());
        $this->parameterCheckerManager->check($authorizationRequest);
        $userAccount = $this->userAccountDiscovery->getCurrentAccount();
        if (null !== $userAccount) {
            $authorizationRequest->setUserAccount($userAccount);
        }

        $authorizationRequestId = $this->authorizationRequestStorage->generateId();
        $this->authorizationRequestStorage->set($authorizationRequestId, $authorizationRequest);

        return $this->authorizationRequestHandler->handle($request, $authorizationRequestId);
    }
}
