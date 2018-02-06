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
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TokenEndpointSource implements Component
{
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
        if (!$configs['endpoint']['token']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.token.path', $configs['endpoint']['token']['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../../Resources/config/endpoint/token'));
        $loader->load('token.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('path')
                        ->info('The token endpoint path')
                        ->defaultValue('/token/get')
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
