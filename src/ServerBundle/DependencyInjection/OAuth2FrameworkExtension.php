<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\DependencyInjection;

use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class OAuth2FrameworkExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var Component[]
     */
    private $components;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param Component[] $components
     */
    public function __construct(string $alias, array $components)
    {
        $this->alias = $alias;
        $this->components = $components;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration($this->getConfiguration($configs, $container), $configs);

        foreach ($this->components as $component) {
            $component->load($config, $container);
        }
    }

    public function getConfiguration(array $configs, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->getAlias(), $this->components);
    }

    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        foreach ($this->components as $component) {
            $result = $component->prepend($container, $config);
            if (0 !== \count($result)) {
                $container->prependExtensionConfig($this->getAlias(), $result);
            }
        }
    }
}
