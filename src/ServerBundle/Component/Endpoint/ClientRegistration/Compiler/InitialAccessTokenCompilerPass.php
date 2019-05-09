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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration\Compiler;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class InitialAccessTokenCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(InitialAccessTokenMiddleware::class) || !$container->hasDefinition('client_registration_endpoint_pipe')) {
            return;
        }

        $client_manager = $container->getDefinition('client_registration_endpoint_pipe');
        $client_manager->addMethodCall('addMiddlewareBeforeLastOne', [new Reference(InitialAccessTokenMiddleware::class)]);
    }
}
