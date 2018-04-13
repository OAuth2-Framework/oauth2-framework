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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Metadata;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\CommonMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Metadata\Compiler\MetadataRouteCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $config = $configs['endpoint']['metadata'];
        $container->setParameter('oauth2_server.endpoint.metadata.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.metadata.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.metadata.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/metadata'));
        $loader->load('metadata.php');

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled();

        $childNode->children()
            ->scalarNode('path')
                ->info('The metadata endpoint path')
                ->defaultValue('/.well-known/openid-configuration')
            ->end()
            ->scalarNode('host')
                ->info('If set, the route will be limited to that host')
                ->defaultValue('')
                ->treatNullLike('')
                ->treatFalseLike('')
            ->end()
        ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $configs): array
    {
        if (!$configs['endpoint']['metadata']['enabled']) {
            return [];
        }
        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $configs)
            );
        }

        return $updatedConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CommonMetadataCompilerPass());
        $container->addCompilerPass(new MetadataRouteCompilerPass());
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
