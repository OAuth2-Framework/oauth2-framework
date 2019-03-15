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

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

final class ConsentEndpointController extends ConsentEndpoint
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(ResponseFactory $responseFactory, SessionInterface $session, ConsentHandler $consentHandler, RouterInterface $router)
    {
        parent::__construct($responseFactory, $session, $consentHandler);
        $this->router = $router;
    }

    protected function getRouteFor(string $action, string $authorizationId): string
    {
        return $this->router->generate($action, ['authorization_id' => $authorizationId]);
    }
}
