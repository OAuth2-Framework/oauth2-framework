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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationEndpointMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('oauth2_server.authorization_endpoint.controller') || !$container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            return;
        }

        $this->loadRoute($container);

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');

        $definition->addMethodCall('setRoute', ['authorization_endpoint', 'oauth2_server_authorization_endpoint']);
        $definition->addMethodCall('setAuthorizationFactory', [new Reference('oauth2_server.authorization_factory')]);
        $definition->addMethodCall('setAuthorizationRequestLoader', [new Reference('oauth2_server.authorization_request_loader')]);

        $this->callFactory($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadRoute(ContainerBuilder $container)
    {
        $path = $container->getParameter('oauth2_server.authorization_endpoint.path');
        $route_loader = $container->getDefinition('oauth2_server.route_loader');
        $route_loader->addMethodCall('addRoute', [
            'authorization_endpoint',
            'oauth2_server.authorization_endpoint.controller',
            'authorizationAction',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['GET', 'POST'], // methods
            '', // condition
        ]);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function callFactory(ContainerBuilder $container)
    {
        $factory = $container->getDefinition('oauth2_server.authorization_endpoint.controller');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_authorization_endpoint_extension');
        foreach ($taggedServices as $id => $attributes) {
            $factory->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
