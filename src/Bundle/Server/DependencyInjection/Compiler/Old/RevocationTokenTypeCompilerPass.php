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

namespace OAuth2Framework\Bundle\Server\TokenRevocationEndpointPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RevocationTokenTypeCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.token_revocation_endpoint')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.token_revocation_endpoint');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server.token_revocation');
        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addRevocationTokenType', [new Reference($id)]);
        }
    }
}
