<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class IdTokenMetadataCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(MetadataBuilder::class) || ! $container->hasDefinition(UserInfo::class)) {
            return;
        }
        $metadata = $container->getDefinition(MetadataBuilder::class);

        $metadata->addMethodCall('setUserinfo', [new Reference(UserInfo::class)]);
        $metadata->addMethodCall(
            'addKeyValuePair',
            ['id_token_signing_alg_values_supported', $container->getParameter(
                'oauth2_server.openid_connect.id_token.signature_algorithms'
            )]
        );
        if ($container->getParameter('oauth2_server.openid_connect.id_token.encryption.enabled') === true) {
            $metadata->addMethodCall(
                'addKeyValuePair',
                ['id_token_encryption_alg_values_supported', $container->getParameter(
                    'oauth2_server.openid_connect.id_token.encryption.key_encryption_algorithms'
                )]
            );
            $metadata->addMethodCall(
                'addKeyValuePair',
                ['id_token_encryption_enc_values_supported', $container->getParameter(
                    'oauth2_server.openid_connect.id_token.encryption.content_encryption_algorithms'
                )]
            );
        }
    }
}
