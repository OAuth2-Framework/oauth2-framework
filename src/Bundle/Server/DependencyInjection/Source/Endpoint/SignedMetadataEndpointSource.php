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

use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class SignedMetadataEndpointSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.algorithm', $config['algorithm']);
        $container->setAlias($path.'.key_set', $config['key_set']);
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'signature';
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
                    return true === $config['enabled'] && empty($config['algorithm']);
                })
                ->thenInvalid('The parameter "algorithm" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['key_set']);
                })
                ->thenInvalid('The parameter "key_set" must be set.')
            ->end()
            ->children()
                ->scalarNode('algorithm')
                    ->info('Signature algorithm used to sign the metadata.')
                ->end()
                ->scalarNode('key_set')
                    ->info('Signature key set.')
                ->end()
            ->end();
    }

    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        parent::prepend($bundleConfig, $path, $container);
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        $this->updateJoseBundleConfigurationForSigner($container, $sourceConfig);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForSigner(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addSigner($container, 'metadata_signature', [$sourceConfig['algorithm']], false, false);
    }
}
