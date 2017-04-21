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

final class AuthorizationEndpointRequestObjectSource extends ActionableSource
{
    /**
     * AuthorizationEndpointRequestObjectSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationEndpointRequestObjectReferenceSource());
        $this->addSubSource(new AuthorizationEndpointRequestObjectEncryptionSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'request_object';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->arrayNode('signature_algorithms')
                    ->info('Supported signature algorithms.')
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
        if (true === $sourceConfig['enabled']) {
            $claim_checkers = ['exp', 'iat', 'nbf', /*'authorization_endpoint_aud'*/]; // FIXME
            $header_checkers = ['crit'];
            $this->updateJoseBundleConfigurationForVerifier($container, ['signature_algorithms' => $sourceConfig['signature_algorithms']]);
            $this->updateJoseBundleConfigurationForChecker($container, ['header_checkers' => $header_checkers, 'claim_checkers' => $claim_checkers]);
            $this->updateJoseBundleConfigurationForDecrypter($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForJWTLoader($container, $sourceConfig);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, array $sourceConfig)
    {
        if (true === $sourceConfig['encryption']['enabled']) {
            ConfigurationHelper::addDecrypter($container, $this->name(), $sourceConfig['encryption']['key_encryption_algorithms'], $sourceConfig['encryption']['content_encryption_algorithms'], ['DEF'], false);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addVerifier($container, $this->name(), $sourceConfig['signature_algorithms'], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForChecker(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addChecker($container, $this->name(), $sourceConfig['header_checkers'], $sourceConfig['claim_checkers'], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForJWTLoader(ContainerBuilder $container, array $sourceConfig)
    {
        $decrypter = null;
        if (true === $sourceConfig['encryption']['enabled']) {
            $decrypter = sprintf('jose.decrypter.%s', $this->name());
        }
        ConfigurationHelper::addJWTLoader($container, $this->name(), sprintf('jose.verifier.%s', $this->name()), sprintf('jose.checker.%s', $this->name()), $decrypter, false);
    }
}
