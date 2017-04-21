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

use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointRequestObjectReferenceSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'reference';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->booleanNode('uris_registration_required')
                    ->info('If true, request object reference Uris must be registered to be used (highly recommended).')
                    ->defaultTrue()
                ->end()
            ->end();
    }
}
