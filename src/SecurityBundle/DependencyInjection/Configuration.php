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

namespace OAuth2Framework\SecurityBundle\DependencyInjection;

use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\MacTokenType\MacToken;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var string
     */
    private $alias;

    /**
     * Configuration constructor.
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->alias);

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('psr7_message_factory')
            ->cannotBeEmpty()
            ->defaultValue('oauth2_security.psr7_message_factory.default')
            ->info('PSR7 requests and responses factory')
            ->end()
            ->end();
        if (\class_exists(BearerToken::class)) {
            $rootNode
                ->children()
                ->arrayNode('bearer_token')
                ->addDefaultsIfNotSet()
                ->canBeDisabled()
                ->children()
                ->scalarNode('realm')
                ->isRequired()
                ->info('The realm displayed in the authentication header')
                ->end()
                ->booleanNode('authorization_header')
                ->defaultTrue()
                ->info('Allow the access token to be sent in the authorization header (recommended).')
                ->end()
                ->booleanNode('request_body')
                ->defaultFalse()
                ->info('Allow the access token to be sent in the request body (not recommended).')
                ->end()
                ->booleanNode('query_string')
                ->defaultFalse()
                ->info('Allow the access token to be sent in the query string (not recommended).')
                ->end()
                ->end()
                ->end()
                ->end();
        }

        if (\class_exists(MacToken::class)) {
            $rootNode
                ->children()
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

        return $treeBuilder;
    }
}
