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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationEndpointRouteCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('authorization_endpoint_pipe')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.authorization.path');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'authorization_endpoint',
            'authorization_endpoint_pipe',
            'dispatch',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['GET', 'POST'], // methods
            '', // condition
        ]);

        /*$definition = $container->getDefinition('oauth2_server.openid_connect.metadata');

        $definition->addMethodCall('setRoute', ['authorization_endpoint', 'oauth2_server_authorization_endpoint']);
        $definition->addMethodCall('setAuthorizationFactory', [new Reference('oauth2_server.authorization_factory')]);
        $definition->addMethodCall('setAuthorizationRequestLoader', [new Reference('oauth2_server.authorization_request_loader')]);

        $this->callFactory($container);*/
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
            'authorization_endpoint_pipe',
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
        $factory = $container->getDefinition('authorization_endpoint_pipe');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_authorization_endpoint_extension');
        foreach ($taggedServices as $id => $attributes) {
            $factory->addMethodCall('addExtension', [new Reference($id)]);
        }
    }
}
