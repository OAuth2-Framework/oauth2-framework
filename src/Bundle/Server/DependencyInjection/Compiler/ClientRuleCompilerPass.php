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

use OAuth2Framework\Bundle\Server\Routing\RouteLoader;
use OAuth2Framework\Component\Server\Model\Client\Rule\RuleManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ClientRuleCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(RuleManager::class)) {
            return;
        }

        $client_manager = $container->getDefinition(RuleManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_client_rule');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('add', [new Reference($id)]);
        }

        $path = $container->getParameter('oauth2_server.endpoint.client_configuration.path');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'client_configuration',
            'client_configuration_endpoint_pipe',
            'dispatch',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['GET', 'PUT', 'DELETE'], // methods
            '', // condition
        ]);

        $path = '/client/register';
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'client_registration',
            'client_registration_endpoint_pipe',
            'dispatch',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['POST'], // methods
            '', // condition
        ]);
    }
}
