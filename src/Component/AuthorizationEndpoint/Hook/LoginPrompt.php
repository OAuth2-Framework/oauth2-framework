<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationCheckerManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoginPrompt implements AuthorizationEndpointHook
{
    public function __construct(
        private UserAuthenticationCheckerManager $userAuthenticationCheckerManager,
        private LoginHandler $loginHandler
    ) {
    }

    public function handle(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ?ResponseInterface {
        $isAuthenticationNeeded = $this->userAuthenticationCheckerManager->isAuthenticationNeeded(
            $authorizationRequest
        );

        if (! $isAuthenticationNeeded && ! $authorizationRequest->hasPrompt('login')) {
            return null;
        }

        if ($authorizationRequest->hasAttribute(
            'user_has_been_authenticated'
        ) && $authorizationRequest->getAttribute('user_has_been_authenticated') === true) {
            return null;
        }

        return $this->loginHandler->handle($request, $authorizationRequestId);
    }
}
