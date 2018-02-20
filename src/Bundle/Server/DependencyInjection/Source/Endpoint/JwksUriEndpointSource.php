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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class JwksUriEndpointSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        //$container->setParameter($path.'.path', $config['path']);
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'jwks_uri';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && (empty($config['path']) || empty($config['key_set']));
                })
                ->thenInvalid('The route name must be set.')
            ->end()
            ->children()
                ->scalarNode('path')
                    ->info('The path of the key set. Something like "/openid_connect/certs"')
                ->end()
                ->scalarNode('key_set')
                    ->info('The public key set to share with third party applications.')
                ->end()
                ->integerNode('max_age')
                    ->info('When share, this value indicates how many seconds the HTTP client should keep the key in cache. Default is 21600 = 6 hours.')
                    ->defaultValue(21600)
                ->end()
            ->end();
    }

    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        if (true === $sourceConfig['enabled']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.endpoint.jwks_uri', 'jwkset', ['value' => $sourceConfig['key_set'], 'path' => $sourceConfig['path']]);
        }
    }
}
