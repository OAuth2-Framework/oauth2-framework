<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ResourceOwnerPasswordCredentialSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('resource_owner_password_credential.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'resource_owner_password_credential';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->booleanNode('issue_refresh_token')
                    ->info('If enabled, a refresh token will be issued with an access token.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('issue_refresh_token_for_public_clients')
                    ->info('If enabled, a refresh token will be issued with an access token when the client is public. This option is useless if the option "issue_refresh_token" is set to false.')
                    ->defaultFalse()
                ->end()
            ->end();
    }
}
