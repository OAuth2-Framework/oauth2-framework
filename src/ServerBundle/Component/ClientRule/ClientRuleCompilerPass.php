<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\ClientRule;

use OAuth2Framework\Component\ClientRule\RuleManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientRuleCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(RuleManager::class)) {
            return;
        }

        $client_manager = $container->getDefinition(RuleManager::class);

        $taggedServices = $container->findTaggedServiceIds('oauth2_server_client_rule');
        foreach ($taggedServices as $id => $attributes) {
            $client_manager->addMethodCall('add', [new Reference($id)]);
        }
    }
}
