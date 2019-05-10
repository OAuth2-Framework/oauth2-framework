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

use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

abstract class ConsentEndpoint extends AbstractEndpoint
{
    /**
     * @var ConsentHandler
     */
    private $consentHandler;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session, ConsentHandler $consentHandler)
    {
        parent::__construct($responseFactory, $session);
        $this->consentHandler = $consentHandler;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorizationId = $this->getAuthorizationId($request);
        $authorization = $this->getAuthorization($authorizationId);
        try {
            $this->consentHandler->prepare($request, $authorizationId, $authorization);
            if (!$this->consentHandler->hasBeenProcessed($request, $authorizationId, $authorization)) {
                $redirectTo = $this->getRouteFor('authorization_process_endpoint', $authorizationId);

                return $this->createRedirectResponse($redirectTo);
            }

            return $this->consentHandler->process($request, $authorizationId, $authorization);
        } catch (OAuth2Error $e) {
            throw new OAuth2AuthorizationException($e->getMessage(), $e->getErrorDescription(), $authorization);
        } catch (\Throwable $e) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization);
        }
    }

    abstract protected function getRouteFor(string $action, string $authorizationId): string;
}
