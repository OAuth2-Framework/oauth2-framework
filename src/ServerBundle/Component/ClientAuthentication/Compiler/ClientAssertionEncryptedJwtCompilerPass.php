<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAssertionEncryptedJwtCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ClientAssertionJwt::class) || $container->getParameter(
            'oauth2_server.client_authentication.client_assertion_jwt.encryption.enabled'
        ) !== true) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $definition->addMethodCall('enableEncryptedAssertions', [
            new Reference('jose.jwe_loader.client_authentication.client_assertion_jwt.encryption'),
            new Reference('jose.key_set.client_authentication.client_assertion_jwt.encryption'),
            '%oauth2_server.client_authentication.client_assertion_jwt.encryption.required%',
        ]);
    }
}
