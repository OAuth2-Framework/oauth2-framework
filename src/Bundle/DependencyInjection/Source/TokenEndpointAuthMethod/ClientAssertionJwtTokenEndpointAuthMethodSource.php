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

namespace OAuth2Framework\Bundle\DependencyInjection\Source\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use Jose\Bundle\JoseFramework\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ClientAssertionJwtTokenEndpointAuthMethodSource extends ActionableSource
{
    /**
     * ClientAssertionJwtTokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new ClientAssertionJwtTokenEndpointAuthMethodEncryptionSupportSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.signature_algorithms', $config['signature_algorithms']);
        $container->setParameter($path.'.claim_checkers', $config['claim_checkers']);
        $container->setParameter($path.'.header_checkers', $config['header_checkers']);
        $container->setParameter($path.'.secret_lifetime', $config['secret_lifetime']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/token_endpoint_auth_method'));
        $loader->load('client_assertion_jwt.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'client_assertion_jwt';
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
                ->thenInvalid('At least one signature algorithm must be set.')
            ->end()
            ->children()
                ->integerNode('secret_lifetime')->defaultValue(60 * 60 * 24 * 14)->min(0)->end()
                ->arrayNode('signature_algorithms')
                    ->info('Supported signature algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('claim_checkers')
                    ->info('Claim checkers for incoming assertions.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('header_checkers')
                    ->info('Header checkers for incoming assertions.')
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
            $this->updateJoseBundleConfigurationForVerifier($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForDecrypter($container, $sourceConfig);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addJWSLoader($container, $this->name(), $sourceConfig['signature_algorithms'], [], ['jws_compact'], false);
        ConfigurationHelper::addClaimChecker($container, $this->name(), [], false);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, array $sourceConfig)
    {
        if (true === $sourceConfig['encryption']['enabled']) {
            ConfigurationHelper::addJWELoader($container, $this->name(), $sourceConfig['encryption']['key_encryption_algorithms'], $sourceConfig['encryption']['content_encryption_algorithms'], ['DEF'], [], ['jwe_compact'], false);
        }
    }
}
