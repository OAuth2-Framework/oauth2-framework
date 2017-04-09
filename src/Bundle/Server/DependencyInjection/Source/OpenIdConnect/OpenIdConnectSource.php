<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\OpenIdConnect;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OpenIdConnectSource extends ActionableSource
{
    /**
     * @var SourceInterface[]
     */
    private $subSources;

    /**
     * OpenIdConnectSource constructor.
     */
    public function __construct()
    {
        $this->subSources = [
            new UserinfoEndpointSource(),
            new IdTokenSource(),
            new AuthorizationEndpointIdTokenHintSource(),
            new PairwiseSubjectSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'openid_connect';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($this->subSources as $source) {
            $source->load($path, $container, $config);
        }
        foreach (['claims_supported', 'claims_locales_supported'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        $loader->load('openid_connect.php');
    }

    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        foreach ($this->subSources as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->arrayNode('claims_supported')
                    ->info('Supported claims.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('claims_locales_supported')
                    ->info('Supported claims locales.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
            ->end();
        foreach ($this->subSources as $source) {
            $source->addConfiguration($node);
        }
    }
}
