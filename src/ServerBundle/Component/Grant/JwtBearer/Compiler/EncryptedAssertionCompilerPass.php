<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler;

use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EncryptedAssertionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(JwtBearerGrantType::class) || $container->getParameter(
            'oauth2_server.grant.jwt_bearer.encryption.enabled'
        ) !== true) {
            return;
        }

        $definition = $container->getDefinition(JwtBearerGrantType::class);
        $definition->addMethodCall('enableEncryptedAssertions', [
            new Reference('jose.jwe_decrypter.oauth2_server.grant.jwt_bearer'),
            new Reference('jose.key_set.oauth2_server.grant.jwt_bearer'),
            '%oauth2_server.grant.jwt_bearer.encryption.required%',
        ]);
    }
}
