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

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\AuthorizationEndpointHook;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\TokenType\TokenTypeGuesser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AuthorizationEndpoint
{
    /**
     * @var null|ConsentRepository
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
    /**
     * @var TokenTypeGuesser
     */
    private $tokenTypeGuesser;
    /**
     * @var ResponseTypeGuesser
     */
    private $responseTypeGuesser;
    /**
     * @var ResponseModeGuesser
     */
    private $responseModeGuesser;

    public function __construct(ResponseFactoryInterface $responseFactory, TokenTypeGuesser $tokenTypeGuesser, ResponseTypeGuesser $responseTypeGuesser, ResponseModeGuesser $responseModeGuesser, ?ConsentRepository $consentRepository, ExtensionManager $extensionManager, AuthorizationRequestStorage $authorizationRequestStorage, LoginHandler $loginHandler, ConsentHandler $consentHandler)
    {
        $this->consentRepository = $consentRepository;
        $this->responseFactory = $responseFactory;
        $this->extensionManager = $extensionManager;
        $this->authorizationRequestStorage = $authorizationRequestStorage;
        $this->loginHandler = $loginHandler;
        $this->consentHandler = $consentHandler;
        $this->tokenTypeGuesser = $tokenTypeGuesser;
        $this->responseTypeGuesser = $responseTypeGuesser;
        $this->responseModeGuesser = $responseModeGuesser;
    }

    public function addHook(AuthorizationEndpointHook $hook): void
    {
        $this->hooks[] = $hook;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationRequestId = $this->authorizationRequestStorage->getId($request);
        if (!$this->authorizationRequestStorage->has($authorizationRequestId)) {
            throw OAuth2Error::invalidRequest('Unable to find the authorization request');
        }
        $authorizationRequest = $this->authorizationRequestStorage->get($authorizationRequestId);

        try {
            foreach ($this->hooks as $hook) {
                $response = $hook->handle($request, $authorizationRequestId, $authorizationRequest);
                $this->authorizationRequestStorage->set($authorizationRequestId, $authorizationRequest);
                if (null !== $response) {
                    return $response;
                }
            }
            if ($authorizationRequest->hasUserAccount()) {
                return $this->processWithAuthenticatedUser($request, $authorizationRequestId, $authorizationRequest);
            }

            return $this->loginHandler->handle($request, $authorizationRequestId);
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
            $isConsentNeeded = null === $this->consentRepository ? true : !$this->consentRepository->hasConsentBeenGiven($authorizationRequest);
            if ($isConsentNeeded) {
                return $this->consentHandler->handle($request, $authorizationRequestId);
            }
            $authorizationRequest->allow();
        }

        return $this->processWithAuthorization($request, $authorizationRequest);
    }

    private function processWithAuthorization(ServerRequestInterface $request, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        $this->extensionManager->process($request, $authorizationRequest);
        if (!$authorizationRequest->isAuthorized()) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.', $authorizationRequest);
        }
        $tokenType = $this->tokenTypeGuesser->find($authorizationRequest);
        $responseType = $this->responseTypeGuesser->get($authorizationRequest);
        $responseType->preProcess($authorizationRequest);
        $responseType->process($authorizationRequest, $tokenType);

        $responseMode = $this->responseModeGuesser->get($authorizationRequest, $responseType);

        return $this->buildResponse($authorizationRequest, $responseMode);
    }

    private function buildResponse(AuthorizationRequest $authorization, ResponseMode $responseMode): ResponseInterface
    {
        $response = $responseMode->buildResponse(
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
