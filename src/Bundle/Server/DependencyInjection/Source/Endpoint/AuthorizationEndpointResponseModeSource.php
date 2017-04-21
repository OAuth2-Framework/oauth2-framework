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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointResponseModeSource extends ArraySource
{
    /**
     * @var SourceInterface[]
     */
    private $subSources;

    /**
     * AuthorizationEndpointSource constructor.
     */
    public function __construct()
    {
        $this->subSources = [
            new AuthorizationEndpointFormPostResponseModeSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('response_mode.php');
        foreach ($this->subSources as $source) {
            $source->load($path, $container, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'response_mode';
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
                ->booleanNode('allow_response_mode_parameter')
                    ->defaultFalse()
                ->end()
            ->end();
        foreach ($this->subSources as $source) {
            $source->addConfiguration($node);
        }
    }
}
