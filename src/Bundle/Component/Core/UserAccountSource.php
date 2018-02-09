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

namespace OAuth2Framework\Bundle\Component\Core;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserAccountSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'user_account';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setAlias('oauth2_server.user_account.repository', $configs['user_account_repository']);
        $container->setAlias('oauth2_server.user_account.manager', $configs['user_account_manager']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->scalarNode('user_account_repository')
                ->info('The user account repository service')
                ->isRequired()
            ->end()
            ->scalarNode('user_account_manager')
                ->info('The user_account manager service')
                ->isRequired()
            ->end()
        ;
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
        //Nothing to do
        return [];
    }
}
