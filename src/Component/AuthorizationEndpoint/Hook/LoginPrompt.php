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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoginPrompt implements AuthorizationEndpointHook
{
    /**
     * @var UserAuthenticationCheckerManager
     */
    private $userAuthenticationCheckerManager;

    /**
     * @var LoginHandler
     */
    private $loginHandler;

    public function __construct(UserAuthenticationCheckerManager $userAuthenticationCheckerManager, LoginHandler $loginHandler)
    {
        $this->userAuthenticationCheckerManager = $userAuthenticationCheckerManager;
        $this->loginHandler = $loginHandler;
    }

    public function handle(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest, string $authorizationRequestId): ?ResponseInterface
    {
        $isAuthenticationNeeded = $this->userAuthenticationCheckerManager->isAuthenticationNeeded($authorizationRequest);
        if (!$isAuthenticationNeeded || !$authorizationRequest->hasPrompt('login')) {
            return null;
        }

        if ($authorizationRequest->hasAttribute('user_has_been_authenticated') && true === $authorizationRequest->getAttribute('user_has_been_authenticated')) {
            return null;
        }

        return $this->loginHandler->process($request, $authorizationRequestId, $authorizationRequest);
    }
}
