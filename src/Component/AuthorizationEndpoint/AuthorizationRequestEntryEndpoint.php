<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthorizationRequestEntryEndpoint
{
    public function __construct(
        private ParameterCheckerManager $parameterCheckerManager,
        private AuthorizationRequestLoader $authorizationRequestLoader,
        private AuthorizationRequestStorage $authorizationRequestStorage,
        private AuthorizationRequestHandler $authorizationRequestHandler,
        private UserAccountDiscovery $userAccountDiscovery
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationRequest = $this->authorizationRequestLoader->load($request->getQueryParams());
        $this->parameterCheckerManager->check($authorizationRequest);
        $userAccount = $this->userAccountDiscovery->getCurrentAccount();
        if ($userAccount !== null) {
            $authorizationRequest->setUserAccount($userAccount);
        }

        $authorizationRequestId = $this->authorizationRequestStorage->generateId();
        $this->authorizationRequestStorage->set($authorizationRequestId, $authorizationRequest);

        return $this->authorizationRequestHandler->handle($request, $authorizationRequestId);
    }
}
