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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\Token;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Component\TokenEndpoint\AuthenticationMethod\AuthenticationMethod;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class TokenEndpointSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    /**
     * TokenEndpointSource constructor.
     */
    public function __construct()
    {
        $this->subComponents = [
            new TokenEndpointAuthMethodSource(),
        ];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
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

        $childNode
            ->children()
                ->scalarNode('path')
                    ->info('The token endpoint path')
                    ->defaultValue('/token/get')
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
