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

use OAuth2Framework\Bundle\Server\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class JwksUriEndpointRouteCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(MetadataBuilder::class) || !$container->hasParameter('oauth2_server.endpoint.jwks_uri.route_name')) {
            return;
        }

        $routeName = $container->getParameter('oauth2_server.endpoint.jwks_uri.route_name');
        $definition = $container->getDefinition(MetadataBuilder::class);
        $definition->addMethodCall('setRoute', ['jwks_uri', $routeName]);
    }
}
