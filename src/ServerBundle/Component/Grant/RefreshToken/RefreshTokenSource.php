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

namespace OAuth2Framework\ServerBundle\Component\Grant\RefreshToken;

use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenGrantType;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use OAuth2Framework\ServerBundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class RefreshTokenSource implements Component
{
    public function name(): string
    {
        return 'refresh_token';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!\class_exists(RefreshTokenGrantType::class) || !$configs['grant']['refresh_token']['enabled']) {
            return;
        }
        $container->setParameter('oauth2_server.grant.refresh_token.lifetime', $configs['grant']['refresh_token']['lifetime']);
        $container->setAlias(RefreshTokenRepository::class, $configs['grant']['refresh_token']['repository']);
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('refresh_token.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (!\class_exists(RefreshTokenGrantType::class)) {
            return;
        }
        $node->children()
            ->arrayNode('refresh_token')
            ->canBeEnabled()
            ->children()
            ->integerNode('lifetime')
            ->defaultValue(60 * 60 * 24 * 7)
            ->min(1)
            ->info('The refresh token lifetime (in seconds)')
            ->end()
            ->scalarNode('repository')
            ->isRequired()
            ->info('The refresh token repository')
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container): void
    {
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
