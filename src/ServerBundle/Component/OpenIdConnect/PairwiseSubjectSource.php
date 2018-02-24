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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PairwiseSubjectSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'pairwise_subject';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['openid_connect']['pairwise_subject']['enabled']) {
            return;
        }

        $container->setAlias('oauth2_server.openid_connect.pairwise.service', $configs['openid_connect']['pairwise_subject']['service']);
        $container->setParameter('oauth2_server.openid_connect.pairwise.is_default', $configs['openid_connect']['pairwise_subject']['is_default']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['service']);
                    })
                    ->thenInvalid('The pairwise subject service must be set.')
                ->end()
                ->children()
                    ->scalarNode('service')
                    ->end()
                    ->booleanNode('is_default')
                        ->defaultTrue()
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
