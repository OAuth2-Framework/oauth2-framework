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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\JwksUri;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $container->setParameter('oauth2_server.endpoint.jwks_uri.enabled', $configs['endpoint']['jwks_uri']['enabled']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->children()
                    ->scalarNode('path')
                        ->info('The path of the key set (e.g. "/openid_connect/certs").')
                        ->isRequired()
                    ->end()
                    ->scalarNode('key_set')
                        ->info('The public key set to share with third party applications.')
                    ->enKd()
                    ->integerNode('max_age')
                        ->info('When share, this value indicates how many seconds the HTTP client should keep the key in cache. Default is 21600 = 6 hours.')
                        ->defaultValue(21600)
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
        $container->addCompilerPass(new JwksUriEndpointRouteCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $configs): array
    {
        $config = $configs['endpoint']['jwks_uri'];
        if (!$config['enabled']) {
            return [];
        }
        ConfigurationHelper::addKeyset($container, 'oauth2_server.endpoint.jwks_uri', 'jwkset', ['value' => $config['key_set']]);
        ConfigurationHelper::addKeyUri($container, 'oauth2_server.endpoint.jwks_uri', ['id' => 'jose.key_set.oauth2_server.endpoint.jwks_uri', 'path' => $config['path'], 'max_age' => $config['max_age']]);

        return [];
    }
}
