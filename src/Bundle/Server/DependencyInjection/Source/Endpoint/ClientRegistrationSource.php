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

final class ClientRegistrationSource extends ActionableSource
{
    /**
     * @var SourceInterface[]
     */
    private $subSections = [];

    /**
     * ClientRegistrationSource constructor.
     */
    public function __construct()
    {
        $this->subSections = [
            new ClientRegistrationInitialAccessTokenSource(),
            new ClientRegistrationSoftwareStatementSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->subSections as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
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
        $loader->load('client_registration.php');

        foreach ($this->subSections as $source) {
            $source->load($path, $container, $config);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'client_registration';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        foreach ($this->subSections as $source) {
            $source->addConfiguration($node);
        }
        $node
            ->children()
                ->scalarNode('path')->defaultValue('/client/management')->end()
            ->end();
    }
}
