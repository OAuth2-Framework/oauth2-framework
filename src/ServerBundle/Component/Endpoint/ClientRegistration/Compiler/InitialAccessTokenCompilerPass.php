<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Endpoint\ClientRegistration\Compiler;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class InitialAccessTokenCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(InitialAccessTokenMiddleware::class) || ! $container->hasDefinition(
            'client_registration_pipe'
        )) {
            return;
        }

        $client_manager = $container->getDefinition('client_registration_pipe');
        $client_manager->addMethodCall('addBeforeLastOne', [new Reference(InitialAccessTokenMiddleware::class)]);
    }
}
