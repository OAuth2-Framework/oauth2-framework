<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\UserInfoEndpoint\UserInfoEndpoint;
use OAuth2Framework\ServerBundle\Service\MetadataBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UserinfoEndpointSignatureCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(UserInfoEndpoint::class) || ! $container->hasDefinition(
            'jose.jws_builder.oauth2_server.openid_connect.id_token_from_userinfo'
        )) {
            return;
        }

        $definition = $container->getDefinition(UserInfoEndpoint::class);
        $definition->addMethodCall('enableSignature', [
            new Reference('jose.jws_builder.oauth2_server.openid_connect.id_token_from_userinfo'),
            new Reference('jose.key_set.oauth2_server.openid_connect.id_token'),
        ]);

        if ($container->hasDefinition(MetadataBuilder::class)) {
            $definition = $container->getDefinition(MetadataBuilder::class);
            $definition->addMethodCall(
                'addKeyValuePair',
                ['userinfo_signing_alg_values_supported', $container->getParameter(
                    'oauth2_server.openid_connect.id_token.signature_algorithms'
                )]
            );
        }
    }
}
