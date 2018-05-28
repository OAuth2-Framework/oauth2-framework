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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler\UserinfoEndpointEncryptionCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserinfoEndpointEncryptionSource implements Component
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'encryption';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['openid_connect']['userinfo_endpoint']['encryption'];
        $container->setParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }

        $container->setParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.key_encryption_algorithms', $config['key_encryption_algorithms']);
        $container->setParameter('oauth2_server.openid_connect.userinfo_endpoint.encryption.content_encryption_algorithms', $config['content_encryption_algorithms']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['key_encryption_algorithms']);
                    })
                    ->thenInvalid('You must set at least one key encryption algorithm.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['content_encryption_algorithms']);
                    })
                    ->thenInvalid('You must set at least one content encryption algorithm.')
                ->end()
                ->children()
                    ->arrayNode('key_encryption_algorithms')
                        ->info('Supported key encryption algorithms.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                        ->treatFalseLike([])
                    ->end()
                    ->arrayNode('content_encryption_algorithms')
                        ->info('Supported content encryption algorithms.')
                        ->useAttributeAsKey('name')
                        ->scalarPrototype()->end()
                        ->treatNullLike([])
                        ->treatFalseLike([])
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new UserinfoEndpointEncryptionCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        $sourceConfig = $config['openid_connect']['userinfo_endpoint'][$this->name()];
        if (!$sourceConfig['enabled']) {
            return [];
        }


        ConfigurationHelper::addJWEBuilder($container, 'oauth2_server.openid_connect.id_token_from_userinfo', $sourceConfig['key_encryption_algorithms'], $sourceConfig['content_encryption_algorithms'], ['DEF'], false);

        return [];
    }
}
