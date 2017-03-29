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
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointPreConfiguredAuthorizationSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('pre_configured_authorization.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'pre_configured_authorization';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->addDefaultsIfNotSet();
    }
}
