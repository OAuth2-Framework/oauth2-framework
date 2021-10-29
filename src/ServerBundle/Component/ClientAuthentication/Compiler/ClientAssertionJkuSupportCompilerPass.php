<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAssertionJkuSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(ClientAssertionJwt::class) || $container->getParameter(
            'oauth2_server.client_authentication.client_assertion_jwt.jku_support'
        ) !== true || ! $container->hasDefinition(JKUFactory::class)) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $definition->addMethodCall('enableJkuSupport', [new Reference(JKUFactory::class)]);
    }
}
