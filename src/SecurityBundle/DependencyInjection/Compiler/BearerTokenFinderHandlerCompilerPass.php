<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BearerTokenFinderHandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('oauth2_security.token_type.bearer_token')) {
            return;
        }

        $client_manager = $container->getDefinition('oauth2_security.token_type.bearer_token');

        $taggedServices = $container->findTaggedServiceIds('oauth2_security_bearer_token_finder');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('addTokenFinder', [new Reference($id)]);
        }
    }
}
