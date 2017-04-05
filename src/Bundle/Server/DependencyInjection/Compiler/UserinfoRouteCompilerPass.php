<?php

declare(strict_types = 1);

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

class UserinfoRouteCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server_userinfo_pipe')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.endpoint.userinfo.path');
        $route_loader = $container->getDefinition(RouteLoader::class);
        $route_loader->addMethodCall('addRoute', [
            'openid_connect_userinfo_endpoint',
            'oauth2_server_userinfo_pipe',
            'dispatch',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['GET'], // methods
            '', // condition
        ]);

        /*if (!$container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');
        $definition->addMethodCall('setUserinfoEndpoint', [new Reference('oauth2_server_userinfo_pipe'), new Reference('oauth2_server.openid_connect.userinfo')]);
        $definition->addMethodCall('setRoute', ['userinfo_endpoint', 'oauth2_server_openid_connect_userinfo_endpoint']);*/
    }
}
