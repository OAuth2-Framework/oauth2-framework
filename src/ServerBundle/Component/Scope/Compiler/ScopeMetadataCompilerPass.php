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

namespace OAuth2Framework\ServerBundle\Component\Scope\Compiler;

use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ScopeMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MetadataBuilder::class) || !$container->hasAlias(ScopeRepository::class)) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);
        $metadata->addMethodCall('setScopeRepository', [new Reference(ScopeRepository::class)]);
    }
}
