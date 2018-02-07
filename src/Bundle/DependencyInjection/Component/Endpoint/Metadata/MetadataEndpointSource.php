<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\Metadata;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class MetadataEndpointSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    /**
     * MetadataEndpointSource constructor.
     */
    public function __construct()
    {
        $this->subComponents = [
            new SignatureSource(),
            new CustomRouteSource(),
            new CustomValuesSource(),
        ];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'metadata';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['metadata']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.metadata.path', $configs['endpoint']['metadata']['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../../Resources/config/endpoint/metadata'));
        //$loader->load('metadata.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled();

        $childNode->children()
            ->scalarNode('path')
                ->defaultValue('/.well-known/openid-configuration')
            ->end()
        ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }
}
