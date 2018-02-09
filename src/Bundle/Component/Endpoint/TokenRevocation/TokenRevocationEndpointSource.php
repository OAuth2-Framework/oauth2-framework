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

namespace OAuth2Framework\Bundle\Component\Endpoint\TokenRevocation;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenRevocationEndpointSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'token_revocation';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['token_revocation']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.token_revocation.path', $configs['endpoint']['token_revocation']['path']);
        $container->setParameter('oauth2_server.endpoint.token_revocation.allow_callback', $configs['endpoint']['token_revocation']['allow_callback']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/token_revocation'));
        $loader->load('revocation.php');
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
                        ->info('The token revocation endpoint path')
                        ->defaultValue('/token/revocation')
                    ->end()
                    ->booleanNode('allow_callback')
                        ->info('If true, GET request with "callback" parameter are allowed.')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}