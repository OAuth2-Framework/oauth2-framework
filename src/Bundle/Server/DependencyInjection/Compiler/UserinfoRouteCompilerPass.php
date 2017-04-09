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
use OAuth2Framework\Bundle\Server\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

        $path = $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.path');
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

        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }

        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('setRoute', ['userinfo_endpoint', 'oauth2_server_openid_connect_userinfo_endpoint']);
        $definition->addMethodCall('addKeyValuePair', ['userinfo_signing_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.signature_algorithms')]);
        if (true === $container->getParameter('oauth2_server.openid_connect.id_token.encryption.enabled')) {
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.encryption.key_encryption_algorithms')]);
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_enc_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.encryption.content_encryption_algorithms')]);
        }

    }
}
