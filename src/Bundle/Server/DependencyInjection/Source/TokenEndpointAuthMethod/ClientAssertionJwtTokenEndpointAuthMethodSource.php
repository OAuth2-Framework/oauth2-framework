<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\TokenEndpointAuthMethod;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ClientAssertionJwtTokenEndpointAuthMethodSource extends ActionableSource
{
    /**
     * @var ClientAssertionJwtTokenEndpointAuthMethodEncryptionSupportSource
     */
    private $encryptionSupport;

    /**
     * ClientAssertionJwtTokenEndpointAuthMethodSource constructor.
     */
    public function __construct()
    {
        $this->encryptionSupport = new ClientAssertionJwtTokenEndpointAuthMethodEncryptionSupportSource();
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        $container->setParameter($path.'.signature_algorithms', $config['signature_algorithms']);
        $container->setParameter($path.'.claim_checkers', $config['claim_checkers']);
        $container->setParameter($path.'.header_checkers', $config['header_checkers']);
        $this->encryptionSupport->load($path, $container, $config);

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
                ->ifTrue(function($config) {
                    return true === $config['enabled'] && empty($config['signature_algorithms']);
                })
                ->thenInvalid('At least one signature algorithm must be set.')
            ->end()
            ->children()
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
        $this->encryptionSupport->addConfiguration($node);
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        if (true === $sourceConfig['enabled']) {
            $this->updateJoseBundleConfigurationForVerifier($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForDecrypter($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForChecker($container, $sourceConfig);
            $this->updateJoseBundleConfigurationForJWTLoader($container, $sourceConfig);
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
