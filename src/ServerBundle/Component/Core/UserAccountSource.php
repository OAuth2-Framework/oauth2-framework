<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Core;

use OAuth2Framework\Component\Core\UserAccount\UserAccountRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserAccountSource implements Component
{
    public function name(): string
    {
        return 'user_account';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $configs[$this->name()];
        if (false === $config['enabled']) {
            return;
        }
        $container->setAlias(UserAccountRepository::class, $configs['user_account']['repository']);
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        $node->children()
            ->arrayNode($this->name())
            ->info('When resource owner can be an end-user, this section is mandatory.')
            ->canBeEnabled()
            ->children()
            ->scalarNode('repository')
            ->info('The user account repository service')
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
