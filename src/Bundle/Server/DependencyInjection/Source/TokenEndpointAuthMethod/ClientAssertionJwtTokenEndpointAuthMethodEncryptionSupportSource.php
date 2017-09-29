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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenEndpointAuthMethod;

use Assert\Assertion;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ClientAssertionJwtTokenEndpointAuthMethodEncryptionSupportSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['required', 'key_encryption_algorithms', 'content_encryption_algorithms'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.key_set', 'jose.key_set.oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.key_set');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'encryption';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['key_encryption_algorithms']);
                })
                ->thenInvalid('At least one key encryption algorithm must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['content_encryption_algorithms']);
                })
                ->thenInvalid('At least one content encryption algorithm must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['key_set']);
                })
                ->thenInvalid('The KeySet must be set.')
            ->end()
            ->children()
                ->booleanNode('required')->defaultFalse()->end()
                ->scalarNode('key_set')->end()
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
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        //$jwkset = json_decode($sourceConfig['key_set'], true);
        //Assertion::isArray($jwkset, 'Invalid key set.');
        ConfigurationHelper::addKeyset($container, 'oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.key_set', 'jwkset', ['value' => $sourceConfig['key_set']]);
    }
}
