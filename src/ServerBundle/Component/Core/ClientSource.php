<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientSource implements Component
{
    public function name(): string
    {
        return 'client';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->setAlias(ClientRepository::class, $configs['client']['repository']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config/core'));
        $loader->load('client.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('repository')
            ->info('The client repository service')
            ->isRequired()
            ->end()
            ->end()
            ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
