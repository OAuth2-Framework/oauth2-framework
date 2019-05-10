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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler;

use OAuth2Framework\ServerBundle\Controller\MetadataController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SignedMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MetadataController::class) || false === $container->getParameter('oauth2_server.endpoint.metadata.signature.enabled')) {
            return;
        }

        $algorithm = $container->getParameter('oauth2_server.endpoint.metadata.signature.algorithm');
        $metadata = $container->getDefinition(MetadataController::class);
        $metadata->addMethodCall('enableSignedMetadata', [
            new Reference('jose.jws_builder.oauth2_server.endpoint.metadata.signature'),
            $algorithm,
            new Reference('jose.key.oauth2_server.endpoint.metadata.signature'),
        ]);
    }
}
