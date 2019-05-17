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

namespace OAuth2Framework\ServerBundle\Controller;

use Base64Url\Base64Url;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequestLoader;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterCheckerManager;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Templating\EngineInterface;

final class AuthorizationEndpointController extends AuthorizationEndpoint
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;
    /**
     * @var EngineInterface
     */
    private $engine;
    /**
     * @var string
     */
    private $loginTemplate;
    /**
     * @var string
     */
    private $consentTemplate;

    public function __construct(ResponseFactoryInterface $responseFactory, AuthorizationRequestLoader $authorizationRequestLoader, ParameterCheckerManager $parameterCheckerManager, UserAccountDiscovery $userAccountDiscovery, ?ConsentRepository $consentRepository, ExtensionManager $extensionManager, AuthorizationRequestStorage $authorizationRequestStorage, SessionInterface $session, EngineInterface $engine, string $loginTemplate, string $consentTemplate)
    {
        parent::__construct($responseFactory, $authorizationRequestLoader, $parameterCheckerManager, $userAccountDiscovery, $consentRepository, $extensionManager, $authorizationRequestStorage);
        $this->session = $session;
        $this->responseFactory = $responseFactory;
        $this->engine = $engine;
        $this->loginTemplate = $loginTemplate;
        $this->consentTemplate = $consentTemplate;
    }

    protected function processWithConsentResponse(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(
            $this->engine->render($this->consentTemplate, [
                'authorization_request_id' => $authorizationRequestId,
                'authorization_request' => $authorizationRequest,
            ]));

        return $response;
    }

    protected function processWithLoginResponse(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ResponseInterface
    {
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(
            $this->engine->render($this->loginTemplate, [
                'authorization_request_id' => $authorizationRequestId,
                'authorization_request' => $authorizationRequest,
            ]));

        return $response;
    }

    protected function getAuthorizationRequestId(ServerRequestInterface $request): string
    {
        return $this->session->remove('OAUTH2_SERVER_AUTHORIZATION_ID') ?? Base64Url::encode(random_bytes(32));
    }
}
