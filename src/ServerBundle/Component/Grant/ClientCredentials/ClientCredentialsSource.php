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

namespace OAuth2Framework\ServerBundle\Component\Grant\ClientCredentials;

use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class ClientCredentialsSource implements Component
{
    public function name(): string
    {
        return 'client_credentials';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\class_exists(ClientCredentialsGrantType::class) || !$configs['grant']['client_credentials']['enabled']) {
            return;
        }
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('client_credentials.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!\class_exists(ClientCredentialsGrantType::class)) {
            return;
        }
        $node->children()
            ->arrayNode('client_credentials')
            ->canBeEnabled()
            ->info('This grant type flow allows confidential clients to get access tokens to manage their own resources.')
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container)
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
