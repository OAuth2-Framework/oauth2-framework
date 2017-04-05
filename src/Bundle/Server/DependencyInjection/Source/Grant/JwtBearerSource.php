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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Grant;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class JwtBearerSource extends ActionableSource
{
    /**
     * @var SourceInterface[]
     */
    private $subSources = [];

    /**
     * JwtBearerSource constructor.
     */
    public function __construct()
    {
        $this->subSources = [
            new JwtBearerEncryptionSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }

        foreach ($this->subSources as $source) {
            $source->load($path, $container, $config);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/grant'));
        $loader->load('jwt_bearer.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'jwt_bearer';
    }

    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function($config) {
                    return true === $config['enabled'] && empty($config['signature_algorithms']);
                })
                ->thenInvalid('The option "signature_algorithms" must contain at least one signature algorithm.')
            ->end()
            ->children()
                ->booleanNode('issue_refresh_token')
                    ->info('If true, a refresh token will be issued with the access token (the refresh token grant type must be enabled).')
                ->end()
                ->arrayNode('signature_algorithms')
                    ->info('Signature algorithms supported by this grant type.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->arrayNode('claim_checkers')
                    ->info('Checkers will verify the JWT claims.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike(['exp', 'iat', 'nbf'])
                ->end()
                ->arrayNode('header_checkers')
                    ->info('Checkers will verify the JWT headers.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike(['crit'])
                ->end()
            ->end();
        foreach ($this->subSources as $source) {
            $source->addConfiguration($node);
        }
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
