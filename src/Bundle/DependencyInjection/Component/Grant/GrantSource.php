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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Grant;

use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class GrantSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'grant';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->registerForAutoconfiguration(GrantType::class)->addTag('oauth2_server_grant_type');
        $container->registerForAutoconfiguration(ResponseType::class)->addTag('oauth2_server_response_type');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('grant.php');

        if ($configs['grant']['authorization_code']['enabled']) {
            $container->setParameter('oauth2_server.grant.authorization_code.min_length', $configs['grant']['authorization_code']['min_length']);
            $container->setParameter('oauth2_server.grant.authorization_code.max_length', $configs['grant']['authorization_code']['max_length']);
            $container->setParameter('oauth2_server.grant.authorization_code.lifetime', $configs['grant']['authorization_code']['lifetime']);
            $container->setParameter('oauth2_server.grant.authorization_code.enforce_pkce', $configs['grant']['authorization_code']['enforce_pkce']);
            $container->setAlias('oauth2_server.grant.authorization_code.repository', $configs['grant']['authorization_code']['repository']);
            $loader->load('authorization_code.php');
        }

        if ($configs['grant']['client_credentials']['enabled']) {
            $container->setParameter('oauth2_server.grant.client_credentials.issue_refresh_token', $configs['grant']['client_credentials']['issue_refresh_token']);
            $loader->load('client_credentials.php');
        }

        if ($configs['grant']['implicit']['enabled']) {
            $loader->load('implicit.php');
        }

        if ($configs['grant']['refresh_token']['enabled']) {
            $container->setParameter('oauth2_server.grant.refresh_token.min_length', $configs['grant']['refresh_token']['min_length']);
            $container->setParameter('oauth2_server.grant.refresh_token.max_length', $configs['grant']['refresh_token']['max_length']);
            $container->setParameter('oauth2_server.grant.refresh_token.lifetime', $configs['grant']['refresh_token']['lifetime']);
            $container->setAlias('oauth2_server.grant.refresh_token.repository', $configs['grant']['refresh_token']['repository']);
            $loader->load('refresh_token.php');
        }

        if ($configs['grant']['resource_owner_password_credential']['enabled']) {
            $loader->load('resource_owner_password_credential.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('authorization_code')
                        ->validate()
                            ->ifTrue(function ($config) {
                                return $config['max_length'] < $config['min_length'];
                            })
                            ->thenInvalid('The option "max_length" must be greater than "min_length".')
                        ->end()
                        ->canBeEnabled()
                        ->children()
                            ->integerNode('min_length')
                                ->defaultValue(50)
                                ->min(0)
                                ->info('Minimum length of the randomly generated authorization code')
                            ->end()
                            ->integerNode('max_length')
                                ->defaultValue(100)
                                ->min(1)
                                ->info('Maximum length of the randomly generated authorization code')
                            ->end()
                            ->integerNode('lifetime')
                                ->defaultValue(30)
                                ->min(1)
                                ->info('Authorization code lifetime (in seconds)')
                            ->end()
                            ->scalarNode('repository')
                                ->isRequired()
                                ->info('The authorization code repository')
                            ->end()
                            ->booleanNode('enforce_pkce')
                                ->defaultFalse()
                                ->info('If true, the PKCE is required for all requests including the ones from confidential clients')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('client_credentials')
                        ->canBeEnabled()
                        ->children()
                            ->booleanNode('issue_refresh_token')
                                ->info('If enabled, a refresh token will be issued with an access token (not recommended)')
                                ->defaultFalse()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('resource_owner_password_credential')
                        ->canBeEnabled()
                    ->end()
                    ->arrayNode('implicit')
                        ->canBeEnabled()
                    ->end()
                    ->arrayNode('refresh_token')
                        ->canBeEnabled()
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
                            ->integerNode('min_length')
                                ->defaultValue(50)
                                ->min(0)
                                ->info('Minimum length of the randomly generated refresh tokens')
                            ->end()
                            ->integerNode('max_length')
                                ->defaultValue(100)
                                ->min(1)
                                ->info('Maximum length of the randomly generated refresh tokens')
                            ->end()
                            ->integerNode('lifetime')
                                ->defaultValue(60 * 60 * 24 * 7)
                                ->min(1)
                                ->info('The refresh token lifetime (in seconds)')
                            ->end()
                            ->scalarNode('repository')
                                ->defaultNull()
                                ->info('The refresh token repository')
                            ->end()
                        ->end()
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
        //Nothing to do
        return [];
    }
}
