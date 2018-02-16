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

namespace OAuth2Framework\Bundle\Component\Endpoint\JwksUri;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JwksUriEndpointSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'jwks_uri';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('path')
                    ->info('The path of the key set (e.g. "/openid_connect/certs").')
                ->end()
                ->scalarNode('key_set')
                    ->info('The public key set to share with third party applications.')
                ->end()
                ->integerNode('max_age')
                    ->info('When share, this value indicates how many seconds the HTTP client should keep the key in cache. Default is 21600 = 6 hours.')
                    ->defaultValue(21600)
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
        $currentPath = '[endpoint][jwks_uri]';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($config, $currentPath);
        if (true === $sourceConfig['enabled']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.endpoint.jwks_uri', 'jwkset', ['value' => $sourceConfig['key_set']]);
            ConfigurationHelper::addKeyUri($container, 'oauth2_server.endpoint.jwks_uri', ['id' => 'jose.key_set.oauth2_server.endpoint.jwks_uri', 'path' => $sourceConfig['path'], 'max_age' => $sourceConfig['max_age']]);
        }

        return [];
    }
}
