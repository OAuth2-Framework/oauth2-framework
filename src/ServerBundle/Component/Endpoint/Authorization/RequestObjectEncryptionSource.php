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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\RequestObjectEncryptionCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestObjectEncryptionSource implements Component
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $configs['endpoint']['authorization']['request_object']['encryption'];
        foreach (['required', 'key_set', 'key_encryption_algorithms', 'content_encryption_algorithms'] as $k) {
            $container->setParameter('oauth2_server.endpoint.authorization.request_object.encryption.'.$k, $config[$k]);
        }
    }

    public function name(): string
    {
        return 'encryption';
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->booleanNode('required')
            ->info('If true, incoming request objects must be encrypted.')
            ->defaultFalse()
            ->end()
            ->scalarNode('key_set')
            ->info('The encryption private keys.')
            ->isRequired()
            ->end()
            ->arrayNode('key_encryption_algorithms')
            ->info('Supported key encryption algorithms.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()->end()
            ->treatNullLike([])
            ->end()
            ->arrayNode('content_encryption_algorithms')
            ->info('Supported content encryption algorithms.')
            ->useAttributeAsKey('name')
            ->scalarPrototype()->end()
            ->treatNullLike([])
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        $sourceConfig = $config['endpoint']['authorization']['request_object']['encryption'];
        if (true === $sourceConfig['enabled']) {
            ConfigurationHelper::addKeyset($container, 'oauth2_server.endpoint.authorization.request_object', 'jwkset', ['value' => $sourceConfig['key_set']]);
            ConfigurationHelper::addJWELoader($container, 'oauth2_server.endpoint.authorization.request_object', ['jwe_compact'], $sourceConfig['key_encryption_algorithms'], $sourceConfig['content_encryption_algorithms'], ['DEF'], [], false);
        }

        return [];
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RequestObjectEncryptionCompilerPass());
    }
}
