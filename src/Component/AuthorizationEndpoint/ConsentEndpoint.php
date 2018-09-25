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
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class ConsentEndpoint extends AbstractEndpoint
{
    private $router;

    public function __construct(MessageFactory $messageFactory, SessionInterface $session, RouterInterface $router)
    {
        parent::__construct($messageFactory, $session);
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $authorizationId = $this->getAuthorizationId($request);
            $authorization = $this->getAuthorization($authorizationId);
            if ($this->processConsent($authorization)) {
                $redirectTo = $this->router->generate('authorization_process_endpoint', ['authorization_id' => $authorizationId]);

                return $this->createRedirectResponse($redirectTo);
            }

            throw $this->buildOAuth2Error($authorization, OAuth2Error::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.');
        } catch (OAuth2Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, null);
        }
    }

    private function getAuthorizationId(ServerRequestInterface $request): string
    {
        $authorizationId = $request->getAttribute('authorization_id');
        if (null === $authorizationId) {
            throw new \InvalidArgumentException('Invalid authorization ID.');
        }

        return $authorizationId;
    }

    abstract protected function processConsent(AuthorizationRequest $authorizationRequest): bool;
}
