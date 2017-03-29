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

final class UserinfoSignatureSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['signature_algorithms'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
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
                    return true === $config['enabled'] && empty($config['signature_algorithms']);
                })
                ->thenInvalid('The option "signature_algorithms" must contain at least one signature algorithm.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return true === $config['enabled'] && empty($config['key_set']);
                })
                ->thenInvalid('The option "key_set" must be set.')
            ->end()
                ->children()
                    ->arrayNode('signature_algorithms')
                        ->info('Signature algorithms used to sign the claims from the Userinfo endpoint.')
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                        ->treatNullLike([])
                    ->end()
                    ->scalarNode('key_set')
                        ->info('Key set that contains a suitable signature key for the selected signature algorithms.')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        $currentPath = $path.'['.$this->name().']';
        $accessor =  PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        if (true === $sourceConfig['enabled']) {
            $this->updateJoseBundleConfigurationForSigner($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForJWTCreator($container, $sourceConfig);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForSigner(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addSigner($container, 'userinfo_endpoint', $sourceConfig['signature_algorithms'], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForJWTCreator(ContainerBuilder $container, array $sourceConfig)
    {
        $encrypter = null;
        /*if (true === $sourceConfig['encryption']['enabled']) { //FIXME: Encryption support
            $encrypter = sprintf('jose.encrypter.%s', $this->name());
        }*/
        ConfigurationHelper::addJWTCreator($container, 'userinfo_endpoint', 'jose.signer.userinfo_endpoint', $encrypter, false);
    }
}
