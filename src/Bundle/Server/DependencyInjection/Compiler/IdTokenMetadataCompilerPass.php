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
use OAuth2Framework\Component\Server\Endpoint\UserInfo\UserInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IdTokenMetadataCompilerPass implements CompilerPassInterface
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

        if ($container->hasDefinition(UserInfo::class)) {
            $metadata->addMethodCall('setUserinfo', [new Reference(UserInfo::class)]);
            $metadata->addMethodCall('addKeyValuePair', ['claims_supported', $container->getParameter('oauth2_server.openid_connect.claims_supported')]);
            $metadata->addMethodCall('addKeyValuePair', ['claims_locales_supported', $container->getParameter('oauth2_server.openid_connect.claims_locales_supported')]);
            $metadata->addMethodCall('addKeyValuePair', ['id_token_signing_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.signature_algorithms')]);
            if (true === $container->getParameter('oauth2_server.openid_connect.id_token.encryption.enabled')) {
                $metadata->addMethodCall('addKeyValuePair', ['id_token_encryption_alg_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.encryption.key_encryption_algorithms')]);
                $metadata->addMethodCall('addKeyValuePair', ['id_token_encryption_enc_values_supported', $container->getParameter('oauth2_server.openid_connect.id_token.encryption.content_encryption_algorithms')]);
            }
        }
    }
}
