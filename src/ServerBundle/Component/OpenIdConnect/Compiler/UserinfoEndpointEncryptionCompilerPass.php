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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserinfoEndpointEncryptionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(UserInfoEndpoint::class) || !$container->hasDefinition('jose.jwe_builder.oauth2_server.openid_connect.id_token_from_userinfo')) {
            return;
        }

        $definition = $container->getDefinition(UserInfoEndpoint::class);
        $definition->addMethodCall('enableEncryption', [new Reference('jose.jwe_builder.oauth2_server.openid_connect.id_token_from_userinfo')]);

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $definition = $container->getDefinition(MetadataBuilder::class);
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.key_encryption_algorithms')]);
            $definition->addMethodCall('addKeyValuePair', ['userinfo_encryption_enc_values_supported', $container->getParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.content_encryption_algorithms')]);
        }
    }
}
