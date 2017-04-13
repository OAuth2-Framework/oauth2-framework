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
use OAuth2Framework\Bundle\Server\TokenEndpointAuthMethod\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ClientJwtAssertionMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(MetadataBuilder::class)) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);

        if ($container->hasDefinition(ClientAssertionJwt::class)) {
            $metadata->addMethodCall('setClientAssertionJwt', [new Reference(ClientAssertionJwt::class)]);
        }
    }
}
