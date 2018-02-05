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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint\ClientRegistration;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class InitialAccessTokenSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'initial_access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['client_registration']['initial_access_token']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.required', $configs['endpoint']['client_registration']['initial_access_token']['required']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.realm', $configs['endpoint']['client_registration']['initial_access_token']['realm']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.authorization_header', $configs['endpoint']['client_registration']['initial_access_token']['authorization_header']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.query_string', $configs['endpoint']['client_registration']['initial_access_token']['query_string']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.request_body', $configs['endpoint']['client_registration']['initial_access_token']['request_body']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.min_length', $configs['endpoint']['client_registration']['initial_access_token']['min_length']);
        $container->setParameter('oauth2_server.endpoint.client_registration.initial_access_token.max_length', $configs['endpoint']['client_registration']['initial_access_token']['max_length']);
        $container->setAlias('oauth2_server.endpoint.client_registration.initial_access_token.repository', $configs['endpoint']['client_registration']['initial_access_token']['repository']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../../../Resources/config/endpoint/client_registration'));
        $loader->load('initial_access_token.php');
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
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['realm']);
                })
                ->thenInvalid('The option "realm" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['repository']);
                })
                ->thenInvalid('The option "repository" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && $config['max_length'] < $config['min_length'];
                })
                ->thenInvalid('The option "max_length" must be greater than "min_length".')
            ->end()
            ->children()
                ->booleanNode('required')
                    ->defaultFalse()
                ->end()
                ->scalarNode('realm')
                    ->defaultNull()
                ->end()
                ->booleanNode('authorization_header')
                    ->defaultTrue()
                ->end()
                ->booleanNode('query_string')
                    ->defaultFalse()
                ->end()
                ->booleanNode('request_body')
                    ->defaultFalse()
                ->end()
                ->integerNode('min_length')
                    ->defaultValue(50)
                    ->min(0)
                ->end()
                ->integerNode('max_length')
                    ->defaultValue(100)
                    ->min(1)
                ->end()
                ->scalarNode('repository')
                    ->defaultNull()
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
