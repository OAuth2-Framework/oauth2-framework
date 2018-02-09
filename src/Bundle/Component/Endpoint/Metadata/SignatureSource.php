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

namespace OAuth2Framework\Bundle\Component\Endpoint\Metadata;

use OAuth2Framework\Bundle\Component\Component;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SignatureSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'signature';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $container->setParameter('oauth2_server.endpoint.metadata.custom_routes', $configs['endpoint']['metadata']['custom_routes']);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(NodeDefinition $node)
    {
        $node->children()
            ->arrayNode('signature')
                ->canBeEnabled()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['algorithm']);
                    })
                    ->thenInvalid('The signature algorithm must be set.')
                ->end()
                ->validate()
                    ->ifTrue(function ($config) {
                        return true === $config['enabled'] && empty($config['key']);
                    })
                    ->thenInvalid('The signature key must be set.')
                ->end()
                ->children()
                    ->scalarNode('algorithm')
                        ->info('Signature algorithm used to sign the metadata.')
                    ->end()
                    ->scalarNode('key')
                        ->info('Signature key.')
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
        //Nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        /*parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        ConfigurationHelper::addJWSBuilder($container, 'metadata_signature', [$sourceConfig['algorithm']], false);

        Assertion::keyExists($bundleConfig['key_set'], 'signature', 'The signature key set must be enabled.');*/
        //ConfigurationHelper::addKeyset($container, 'signed_metadata_endpoint.key_set.signature', 'jwkset', ['value' => $bundleConfig['key_set']['signature']]);
        return [];
    }
}
