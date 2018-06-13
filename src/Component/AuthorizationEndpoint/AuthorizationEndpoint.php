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
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountCheckerManager;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AuthorizationEndpoint implements MiddlewareInterface
{
    /**
     * @var UserAccountDiscovery
     */
    protected $userAccountDiscovery;

    /**
     * @var UserAccountCheckerManager
     */
    protected $userAccountCheckerManager;

    /**
     * @var ExtensionManager
     */
    protected $consentScreenExtensionManager;

    /**
     * @var AuthorizationRequestLoader
     */
    protected $authorizationRequestLoader;

    /**
     * @var ParameterCheckerManager
     */
    protected $parameterCheckerManager;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * AuthorizationEndpoint constructor.
     *
     * @param MessageFactory             $messageFactory
     * @param AuthorizationRequestLoader $authorizationRequestLoader
     * @param ParameterCheckerManager    $parameterCheckerManager
     * @param UserAccountDiscovery       $userAccountDiscovery
     * @param UserAccountCheckerManager  $userAccountCheckerManager
     * @param ExtensionManager           $consentScreenExtensionManager
     */
    public function __construct(MessageFactory $messageFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, UserAccountCheckerManager $userAccountCheckerManager, ExtensionManager $consentScreenExtensionManager)
    {
        $this->messageFactory = $messageFactory;
        $this->authorizationRequestLoader = $authorizationRequestLoader;
        $this->parameterCheckerManager = $parameterCheckerManager;
        $this->userAccountDiscovery = $userAccountDiscovery;
        $this->userAccountCheckerManager = $userAccountCheckerManager;
        $this->consentScreenExtensionManager = $consentScreenExtensionManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return ResponseInterface
     */
    abstract protected function redirectToLoginPage(ServerRequestInterface $request, Authorization $authorization): ResponseInterface;

    /**
     * @param ServerRequestInterface $request
     * @param Authorization          $authorization
     *
     * @return ResponseInterface
     */
    abstract protected function processConsentScreen(ServerRequestInterface $request, Authorization $authorization): ResponseInterface;

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorization = $this->createAuthorizationFromRequest($request);
            $isFullyAuthenticated = null;
            $userAccount = $this->userAccountDiscovery->find($isFullyAuthenticated);
            if (!is_bool($isFullyAuthenticated)) {
                $isFullyAuthenticated = false;
            }
            if (null !== $userAccount) {
                $authorization = $authorization->withUserAccount($userAccount, $isFullyAuthenticated);
            }
            $this->userAccountCheckerManager->check($authorization, $userAccount, $isFullyAuthenticated);
            if (null === $userAccount) {
                return $this->redirectToLoginPage($request, $authorization);
            }
            $authorization = $this->consentScreenExtensionManager->processBefore($request, $authorization);

            return $this->processConsentScreen($request, $authorization);
        } catch (OAuth2AuthorizationException $e) {
            throw $e;
        } catch (Exception\ProcessAuthorizationException $e) {
            $authorization = $e->getAuthorization();
            $authorization = $this->consentScreenExtensionManager->processAfter($request, $authorization);
            if (false === $authorization->isAuthorized()) {
                $this->throwRedirectionException($authorization, OAuth2Message::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.');
            }

            $responseType = $authorization->getResponseType();

            try {
                $authorization = $responseType->prePocess($authorization);
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

    /**
     * @param Authorization $authorization
     *
     * @return ResponseInterface
     */
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

    /**
     * @param Authorization $authorization
     * @param string        $error
     * @param string        $errorDescription
     */
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

        throw new OAuth2Message(302, $error, $errorDescription, $params);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return Authorization
     */
    public function createAuthorizationFromRequest(ServerRequestInterface $request): Authorization
    {
        $authorization = $this->authorizationRequestLoader->load($request);
        $authorization = $this->parameterCheckerManager->process($authorization);

        return $authorization;
    }
}
