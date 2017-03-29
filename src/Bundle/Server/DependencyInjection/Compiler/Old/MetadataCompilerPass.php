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

namespace OAuth2Framework\Bundle\Server\ScopeManagerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('oauth2_server.scope.available_scope') || !$container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.openid_connect.metadata');
        $definition->addMethodCall('set', ['scopes_supported', $container->getParameter('oauth2_server.scope.available_scope')]);
    }
}
