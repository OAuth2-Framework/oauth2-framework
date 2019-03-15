<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class AccessTokenHandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('oauth2_security.access_token_handler_manager')) {
            return;
        }

        $client_manager = $container->getDefinition('oauth2_security.access_token_handler_manager');

        $taggedServices = $container->findTaggedServiceIds('oauth2_security_token_handler');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('add', [new Reference($id)]);
        }
    }
}
