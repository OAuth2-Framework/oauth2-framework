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

namespace OAuth2Framework\ServerBundle\Controller;

use OAuth2Framework\Component\AuthorizationEndpoint\LoginEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use Psr\Http\Message\ResponseFactory;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

final class LoginEndpointController extends LoginEndpoint
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session, LoginHandler $loginHandler, RouterInterface $router)
    {
        parent::__construct($responseFactory, $session, $loginHandler);
        $this->router = $router;
    }

    protected function getRouteFor(string $action, string $authorizationId): string
    {
        return $this->router->generate($action, ['authorization_id' => $authorizationId]);
    }
}
