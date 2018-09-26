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

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class ProcessEndpoint extends AbstractEndpoint
{
    private $extensionManager;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session, ExtensionManager $extensionManager)
    {
        parent::__construct($responseFactory, $session);
        $this->extensionManager = $extensionManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorizationId = $this->getAuthorizationId($request);
            $authorization = $this->getAuthorization($authorizationId);
            $this->extensionManager->process($request, $authorization);
            if (!$authorization->isAuthorized()) {
                throw $this->buildOAuth2Error($authorization, OAuth2Error::ERROR_ACCESS_DENIED, 'The resource owner denied access to your client.');
            }
            $responseType = $authorization->getResponseType();
            try {
                $authorization = $responseType->preProcess($authorization);
                $authorization = $responseType->process($authorization);
            } catch (OAuth2Error $e) {
                throw $this->buildOAuth2Error($authorization, $e->getMessage(), $e->getErrorDescription());
            }

            return $this->buildResponse($authorization);
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, null);
        }
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
