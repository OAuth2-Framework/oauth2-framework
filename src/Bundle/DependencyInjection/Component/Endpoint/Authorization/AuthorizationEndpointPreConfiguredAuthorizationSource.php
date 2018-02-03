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

final class AuthorizationEndpointPreConfiguredAuthorizationSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setAlias($path.'.event_store', $config['event_store']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__ . '/../../../Resources/config/endpoint'));
        $loader->load('pre_configured_authorization.php');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'pre_configured_authorization';
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['event_store']);
                })
                ->thenInvalid('The option "event_store" must be set.')
            ->end()
            ->children()
                ->scalarNode('event_store')->defaultNull()->end()
            ->end();
    }
}
