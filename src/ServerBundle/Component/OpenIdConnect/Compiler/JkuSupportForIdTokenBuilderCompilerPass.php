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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class JkuSupportForIdTokenBuilderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(JKUFactory::class) || !$container->hasDefinition(IdTokenBuilderFactory::class)) {
            return;
        }

        $definition = $container->getDefinition(IdTokenBuilderFactory::class);
        $definition->addMethodCall('enableJkuSupport', [new Reference(JKUFactory::class)]);
    }
}
