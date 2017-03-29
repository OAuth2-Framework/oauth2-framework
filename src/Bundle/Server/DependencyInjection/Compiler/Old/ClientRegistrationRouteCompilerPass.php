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

namespace OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientRegistrationRouteCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.client_registration_controller')) {
            return;
        }

        $path = $container->getParameter('oauth2_server.client_manager.management.registration_path');
        $route_loader = $container->getDefinition('oauth2_server.route_loader');
        $route_loader->addMethodCall('addRoute', [
            'client_registration',
            'oauth2_server.client_registration_controller',
            'registerAction',
            $path, // path
            [], // defaults
            [], // requirements
            [], // options
            '', // host
            ['https'], // schemes
            ['POST'], // methods
            '', // condition
        ]);

        if (!$container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');
        $definition->addMethodCall('setRoute', ['registration_client_uri', 'oauth2_server_client_registration']);
    }
}
