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

namespace OAuth2Framework\ServerBundle\Component\Grant\AuthorizationCode;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeGrantType;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class AuthorizationCodeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'authorization_code';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if ($configs['grant']['authorization_code']['enabled']) {
            $container->setParameter('oauth2_server.grant.authorization_code.min_length', $configs['grant']['authorization_code']['min_length']);
            $container->setParameter('oauth2_server.grant.authorization_code.max_length', $configs['grant']['authorization_code']['max_length']);
            $container->setParameter('oauth2_server.grant.authorization_code.lifetime', $configs['grant']['authorization_code']['lifetime']);
            $container->setParameter('oauth2_server.grant.authorization_code.enforce_pkce', $configs['grant']['authorization_code']['enforce_pkce']);
            $container->setAlias('oauth2_server.grant.authorization_code.repository', $configs['grant']['authorization_code']['repository']);

            $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
            $loader->load('authorization_code.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode('authorization_code')
                ->validate()
                    ->ifTrue(function ($config) {
                        return $config['max_length'] < $config['min_length'];
                    })
                    ->thenInvalid('The option "max_length" must be greater than "min_length".')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return $config['enabled'] && !class_exists(AuthorizationCodeGrantType::class);
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
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PKCEMethodCompilerPass());
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
