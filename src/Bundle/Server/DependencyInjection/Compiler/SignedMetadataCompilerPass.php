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

use OAuth2Framework\Bundle\Server\Controller\MetadataController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SignedMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(MetadataController::class) || false === $container->getParameter('oauth2_server.endpoint.metadata.signature.enabled')) {
            return;
        }

        $signatureAlgorithm = $container->getParameter('oauth2_server.endpoint.metadata.signature.algorithm');
        $keySey = $container->getAlias('oauth2_server.endpoint.metadata.signature.key_set');
        $metadata = $container->getDefinition(MetadataController::class);
        $metadata->addMethodCall('enableSignedMetadata', [new Reference('jose.jws_builder.metadata_signature'), $signatureAlgorithm, new Reference($keySey)]);
    }
}
