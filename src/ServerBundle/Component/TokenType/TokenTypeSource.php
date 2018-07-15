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

namespace OAuth2Framework\ServerBundle\Component\TokenType;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\MacTokenType\MacToken;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenTypeSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'token_type';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config/token_type'));
        $loader->load('token_type.php');

        $container->setParameter('oauth2_server.token_type.default', $configs['token_type']['default']);
        $container->setParameter('oauth2_server.token_type.allow_token_type_parameter', $configs['token_type']['allow_token_type_parameter']);

        if (\class_exists(BearerToken::class) && $configs['token_type']['bearer_token']['enabled']) {
            $loader->load('bearer_token.php');
        }
        if (\class_exists(MacToken::class) && $configs['token_type']['mac_token']['enabled']) {
            $container->setParameter('oauth2_server.token_type.mac_token.min_length', $configs['token_type']['mac_token']['min_length']);
            $container->setParameter('oauth2_server.token_type.mac_token.max_length', $configs['token_type']['mac_token']['max_length']);
            $container->setParameter('oauth2_server.token_type.mac_token.algorithm', $configs['token_type']['mac_token']['algorithm']);
            $container->setParameter('oauth2_server.token_type.mac_token.timestamp_lifetime', $configs['token_type']['mac_token']['timestamp_lifetime']);
            $loader->load('mac_token.php');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $child = $node->children()
            ->arrayNode($this->name())
                ->isRequired()
                ->children()
                    ->scalarNode('default')
                        ->defaultValue('bearer')
                        ->info('The default token type used for access token issuance.')
                    ->end()
                    ->booleanNode('allow_token_type_parameter')
                        ->defaultFalse()
                        ->info('If true, the "token_type" parameter will be allowed in requests.')
                    ->end()
                ->end();

        if (\class_exists(BearerToken::class)) {
            $child->children()
                ->arrayNode('bearer_token')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                ->end()
            ->end();
        }

        if (\class_exists(MacToken::class)) {
            $child->children()
                ->arrayNode('mac_token')
                    ->addDefaultsIfNotSet()
                    ->canBeDisabled()
                    ->validate()
                        ->ifTrue(function ($config) {
                            return $config['min_length'] > $config['max_length'];
                        })
                        ->thenInvalid('The option "min_length" must not be greater than "max_length".')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($config) {
                            return !\in_array($config['algorithm'], ['hmac-sha-256', 'hmac-sha-1'], true);
                        })
                        ->thenInvalid('The algorithm is not supported. Please use one of the following one: "hmac-sha-1", "hmac-sha-256".')
                    ->end()
                    ->children()
                        ->integerNode('min_length')
                            ->defaultValue(50)
                            ->min(1)
                            ->info('Minimum length for the generated MAC key')
                        ->end()
                        ->integerNode('max_length')
                            ->defaultValue(100)
                            ->min(2)
                            ->info('Maximum length for the generated MAC key')
                        ->end()
                        ->scalarNode('algorithm')
                            ->defaultValue('hmac-sha-256')
                            ->info('Hashing algorithm. Must be either "hmac-sha-1" or "hmac-sha-256"')
                        ->end()
                        ->integerNode('timestamp_lifetime')
                            ->defaultValue(10)
                            ->min(1)
                            ->info('Default lifetime of the MAC')
                        ->end()
                    ->end()
                ->end()
            ->end();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TokenTypeCompilerPass());
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
