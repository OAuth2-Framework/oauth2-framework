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

namespace OAuth2Framework\Bundle\DependencyInjection\Component\Grant;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\DependencyInjection\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class JwtBearerEncryptionSource implements Component
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['key_encryption_algorithms', 'content_encryption_algorithms', 'required'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        //$container->setAlias($path.'.key_set', 'jose.key_set.jwt_bearer.key_set.encryption');
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'encryption';
    }

    public function getNodeDefinition(NodeDefinition $node)
    {
        $node
            ->children()
                ->booleanNode('required')
                    ->info('If set to true, all ID Token sent to the server must be encrypted.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('key_encryption_algorithms')
                    ->info('Supported key encryption algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('content_encryption_algorithms')
                    ->info('Supported content encryption algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);

        Assertion::keyExists($bundleConfig['key_set'], 'encryption', 'The encryption key set must be enabled.');
        //ConfigurationHelper::addKeyset($container, 'jwt_bearer.key_set.encryption', 'jwkset', ['value' => $bundleConfig['key_set']['encryption']]);
    }
}
