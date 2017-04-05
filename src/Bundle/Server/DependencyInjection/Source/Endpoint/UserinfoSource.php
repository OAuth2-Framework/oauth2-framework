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
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class UserinfoSource extends ActionableSource
{
    /**
     * @var SourceInterface[]
     */
    private $subSources = [];

    /**
     * UserinfoSource constructor.
     */
    public function __construct()
    {
        $this->subSources = [
            new UserinfoSignatureSource(),
            //new UserinfoEncryptionSource(), //FIXME
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->subSources as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.path', $config['path']);
        foreach ($this->subSources as $source) {
            $source->load($path, $container, $config);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('userinfo.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'userinfo';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->scalarNode('path')->defaultValue('/userinfo')->end()
            ->end();
        foreach ($this->subSources as $source) {
            $source->addConfiguration($node);
        }
    }
}
