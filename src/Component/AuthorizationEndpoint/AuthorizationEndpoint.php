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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\AuthorizationEndpointHook;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AuthorizationEndpoint
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
     * @var ConsentRepository|null
     */
    private $consentRepository;

    /**
     * @var AuthorizationEndpointHook[]
     */
    private $hooks = [];
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var ExtensionManager
     */
    private $extensionManager;
    /**
     * @var AuthorizationRequestStorage
     */
    private $authorizationRequestStorage;
    /**
     * @var LoginHandler
     */
    private $loginHandler;
    /**
     * @var ConsentHandler
     */
    private $consentHandler;

    public function __construct(ResponseFactoryInterface $responseFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, ?ConsentRepository $consentRepository, ExtensionManager $extensionManager, AuthorizationRequestStorage $authorizationRequestStorage, LoginHandler $loginHandler, ConsentHandler $consentHandler)
    {
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->userAccountDiscovery = $userAccountDiscovery;
        $this->consentRepository = $consentRepository;
        $this->responseFactory = $responseFactory;
        $this->extensionManager = $extensionManager;
        $this->authorizationRequestStorage = $authorizationRequestStorage;
        $this->loginHandler = $loginHandler;
        $this->consentHandler = $consentHandler;
    }

    public function add(AuthorizationEndpointHook $hook): void
    {
        $this->hooks[] = $hook;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationRequestId = $this->authorizationRequestStorage->getId($request);
        $authorizationRequest = $this->authorizationRequestStorage->remove($authorizationRequestId);

        try {
            if (null === $authorizationRequest) {
                $authorizationRequest = $this->loadAuthorization($request);
                $userAccount = $this->userAccountDiscovery->getCurrentAccount();
                if (null !== $userAccount) {
                    $authorizationRequest->setUserAccount($userAccount);
                }
            }

            foreach ($this->hooks as $hook) {
                $response = $hook->handle($request, $authorizationRequest, $authorizationRequestId);
                if (null !== $response) {
                    $authorizationRequestId = $this->authorizationRequestStorage->getId($request);
                    $this->authorizationRequestStorage->set($authorizationRequestId, $authorizationRequest);

                    return $response;
                }
            }
            if ($authorizationRequest->hasUserAccount()) {
                return $this->processWithAuthenticatedUser($request, $authorizationRequestId, $authorizationRequest);
            }

            return $this->loginHandler->process($request, $authorizationRequestId, $authorizationRequest);
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorizationRequest, $e);
        } catch (Throwable $e) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorizationRequest, $e);
        }
    }

    private function processWithAuthenticatedUser(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        if (!$authorizationRequest->hasConsentBeenGiven()) {
            $isConsentNeeded = null !== $this->consentRepository || !$this->consentRepository->hasConsentBeenGiven($authorizationRequest);
            if ($isConsentNeeded) {
                return $this->consentHandler->process($request, $authorizationRequestId, $authorizationRequest);
            }
        }

        return $this->processWithAuthorization($request, $authorizationRequest);
    }

    private function processWithAuthorization(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        $this->extensionManager->process($request, $authorizationRequest);
        if (!$authorizationRequest->isAuthorized()) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.', $authorizationRequest);
        }
        $responseType = $authorizationRequest->getResponseType();
        $responseType->preProcess($authorizationRequest);
        $responseType->process($authorizationRequest);

        return $this->buildResponse($authorizationRequest);
    }

    private function loadAuthorization(ServerRequestInterface $request): AuthorizationRequest
    {
        $authorization = $this->authorizationRequestLoader->load($request);
        $this->parameterCheckerManager->check($authorization);

        return $authorization;
    }

    private function buildResponse(AuthorizationRequest $authorization): ResponseInterface
    {
        $response = $authorization->getResponseMode()->buildResponse(
            $this->responseFactory->createResponse(),
            $authorization->getRedirectUri(),
            $authorization->getResponseParameters()
        );
        foreach ($authorization->getResponseHeaders() as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
