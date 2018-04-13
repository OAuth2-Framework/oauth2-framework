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

namespace OAuth2Framework\ServerBundle\DependencyInjection\Compiler;

use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserinfoEndpointEncryptionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(UserInfoEndpoint::class) || !$container->hasDefinition('jose.jwe_builder.oauth2_server.userinfo')) {
            return;
        }

        $definition = $container->getDefinition(UserInfoEndpoint::class);
        $definition->addMethodCall('enableEncryption', [new Reference('jose.jwe_builder.oauth2_server.userinfo')]);

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $definition = $container->getDefinition(MetadataBuilder::class);
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.key_encryption_algorithms')]);
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_enc_values_supported', $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.content_encryption_algorithms')]);
        }
    }
}
