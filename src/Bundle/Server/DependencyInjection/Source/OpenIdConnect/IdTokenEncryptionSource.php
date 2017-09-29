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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\OpenIdConnect;

use Assert\Assertion;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class IdTokenEncryptionSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['key_encryption_algorithms', 'content_encryption_algorithms'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.key_set', 'jose.key_set.id_token.key_set.encryption');
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

        if (true === $sourceConfig['enabled']) {
            $this->updateJoseBundleConfigurationForEncrypter($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForDecrypter($container, $sourceConfig);
            //$jwkset = json_decode($sourceConfig['key_set'], true);
            //Assertion::isArray($jwkset, 'Invalid key set.');
            ConfigurationHelper::addKeyset($container, 'id_token.key_set.encryption', 'jwkset', ['value' => $sourceConfig['key_set']]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'encryption';
    }

    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->scalarNode('key_set')
                    ->info('Key set that contains a suitable encryption key for the selected encryption algorithms.')
                    ->defaultNull()
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
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForEncrypter(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addJWEBuilder($container, 'id_token', $sourceConfig['key_encryption_algorithms'], $sourceConfig['content_encryption_algorithms'], ['DEF'], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addJWELoader($container, 'id_token', $sourceConfig['key_encryption_algorithms'], $sourceConfig['content_encryption_algorithms'], ['DEF'], [], ['jwe_compact'], false);
    }
}
