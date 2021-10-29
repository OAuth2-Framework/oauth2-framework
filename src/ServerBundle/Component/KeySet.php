<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KeySet implements Component
{
    public function name(): string
    {
        return 'key_set';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('signature')
            ->defaultNull()
            ->end()
            ->scalarNode('encryption')
            ->defaultNull()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        //Nothing to do
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if ($config['key_set']['signature'] !== null) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.signature', 'jwkset', [
                'value' => $config['key_set']['signature'],
            ]);
        }
        if ($config['key_set']['encryption'] !== null) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.key_set.encryption', 'jwkset', [
                'value' => $config['key_set']['encryption'],
            ]);
        }

        return [];
    }
}
