<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use OAuth2Framework\Component\Server\Middleware\InitialAccessTokenMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class InitialAccessTokenCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(InitialAccessTokenMiddleware::class) || !$container->hasDefinition('client_registration_endpoint_pipe')) {
            return;
        }

        $client_manager = $container->getDefinition('client_registration_endpoint_pipe');
        $client_manager->addMethodCall('addMiddlewareBeforeLastOne', [new Reference(InitialAccessTokenMiddleware::class)]);
    }
}
