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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointResponseModeSource implements Component
{
    /**
     * AuthorizationEndpointSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationEndpointFormPostResponseModeSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/endpoint'));
        $loader->load('response_mode.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'response_mode';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->children()
                ->booleanNode('allow_response_mode_parameter')
                    ->defaultFalse()
                ->end()
            ->end();
    }
}
