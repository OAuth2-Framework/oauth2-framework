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

use Http\Message\MessageFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AuthorizationEndpoint implements MiddlewareInterface
{
    protected $messageFactory;

    protected $authorizationRequestLoader;

    protected $parameterCheckerManager;

    protected $userAccountDiscovery;

    protected $userAccountCheckerManager;

    protected $extensionManager;

    public function __construct(MessageFactory $messageFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, UserAccountCheckerManager $userAccountCheckerManager, ExtensionManager $extensionManager)
    {
        $this->messageFactory = $messageFactory;
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->userAccountDiscovery = $userAccountDiscovery;
        $this->userAccountCheckerManager = $userAccountCheckerManager;
        $this->extensionManager = $extensionManager;
    }

    abstract protected function redirectToLoginPage(ServerRequestInterface $request, Authorization $authorization): ResponseInterface;

    abstract protected function processConsentScreen(ServerRequestInterface $request, Authorization $authorization): ResponseInterface;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorization = $this->createAuthorizationFromRequest($request);
            $isFullyAuthenticated = null;
            $userAccount = $this->userAccountDiscovery->find($isFullyAuthenticated);
            if (!\is_bool($isFullyAuthenticated)) {
                $isFullyAuthenticated = false;
            }
            if (null !== $userAccount) {
                $authorization->setUserAccount($userAccount, $isFullyAuthenticated);
            }
            $this->userAccountCheckerManager->check($authorization, $userAccount, $isFullyAuthenticated);
            if (null === $userAccount) {
                return $this->redirectToLoginPage($request, $authorization);
            }
            $authorization = $this->extensionManager->processBefore($request, $authorization);

            return $this->processConsentScreen($request, $authorization);
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (Exception\ProcessAuthorizationException $e) {
            $authorization = $e->getAuthorization();
            $authorization = $this->extensionManager->processAfter($request, $authorization);
            if (false === $authorization->isAuthorized()) {
                $this->throwRedirectionException($authorization, OAuth2Message::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.');
            }

            $responseType = $authorization->getResponseType();

            try {
                $authorization = $responseType->preProcess($authorization);
                $authorization = $responseType->process($authorization);
            } catch (OAuth2Message $e) {
                $this->throwRedirectionException($authorization, $e->getMessage(), $e->getErrorDescription());
            }

            return $this->buildResponse($authorization);
        } catch (Exception\CreateRedirectionException $e) {
            $this->throwRedirectionException($e->getAuthorization(), $e->getMessage(), $e->getDescription());
        } catch (Exception\ShowConsentScreenException $e) {
            return $this->processConsentScreen($request, $e->getAuthorization());
        } catch (Exception\RedirectToLoginPageException $e) {
            return $this->redirectToLoginPage($request, $e->getAuthorization());
        }
    }

    protected function buildResponse(Authorization $authorization): ResponseInterface
    {
        $response = $authorization->getResponseMode()->buildResponse(
            $this->messageFactory->createResponse(),
            $authorization->getRedirectUri(),
            $authorization->getResponseParameters()
        );
        foreach ($authorization->getResponseHeaders() as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    protected function throwRedirectionException(Authorization $authorization, string $error, string $errorDescription)
    {
        $params = $authorization->getResponseParameters();
        if (null === $authorization->getResponseMode() || null === $authorization->getRedirectUri()) {
            throw new OAuth2Message(400, $error, $errorDescription);
        }
        $params += [
            'response_mode' => $authorization->getResponseMode(),
            'redirect_uri' => $authorization->getRedirectUri(),
        ];

        throw new OAuth2Message(303, $error, $errorDescription, $params);
    }

    public function createAuthorizationFromRequest(ServerRequestInterface $request): Authorization
    {
        $authorization = $this->authorizationRequestLoader->load($request);
        $authorization = $this->parameterCheckerManager->process($authorization);

        return $authorization;
    }
}
