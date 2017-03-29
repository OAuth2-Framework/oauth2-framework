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

namespace OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class JwksUriMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.openid_connect.metadata') || !$container->hasParameter('oauth2_server.openid_connect.jwks_uri.route_name')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');
        $route_name = $container->getParameter('oauth2_server.openid_connect.jwks_uri.route_name');
        $route_parameters = $container->getParameter('oauth2_server.openid_connect.jwks_uri.route_parameters');

        $definition->addMethodCall('setRoute', ['jwks_uri', $route_name, $route_parameters]);
    }
}
