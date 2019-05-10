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

use OAuth2Framework\Component\AuthorizationEndpoint\Extension\ExtensionManager;
use OAuth2Framework\Component\AuthorizationEndpoint\ProcessEndpoint;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

final class ProcessEndpointController extends ProcessEndpoint
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(ResponseFactoryInterface $responseFactory, SessionInterface $session, ExtensionManager $extensionManager, RouterInterface $router)
    {
        parent::__construct($responseFactory, $session, $extensionManager);
        $this->router = $router;
    }

    protected function getRouteFor(string $action, string $authorizationId): string
    {
        return $this->router->generate($action, ['authorization_id' => $authorizationId]);
    }
}
