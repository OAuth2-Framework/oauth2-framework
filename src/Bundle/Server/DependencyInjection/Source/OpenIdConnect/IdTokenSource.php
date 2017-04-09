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

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ArraySource;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\SourceInterface;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class IdTokenSource extends ArraySource
{
    /**
     * @var SourceInterface[]
     */
    private $subSources = [];

    /**
     * UserinfoSource constructor.
     */
    public function __construct()
    {
        $this->subSources = [
            new IdTokenResponseTypeSource(),
            new IdTokenEncryptionSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(array $bundleConfig, string $path, ContainerBuilder $container)
    {
        foreach ($this->subSources as $source) {
            $source->prepend($bundleConfig, $path.'['.$this->name().']', $container);
        }
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);
        $this->updateJoseBundleConfigurationForSigner($container, $sourceConfig);
        $this->updateJoseBundleConfigurationForVerifier($container, $sourceConfig);
        $this->updateJoseBundleConfigurationForChecker($container, $sourceConfig);
        $this->updateJoseBundleConfigurationForJWTLoader($container, $sourceConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['lifetime', 'default_signature_algorithm', 'signature_algorithms', 'claim_checkers', 'header_checkers'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.key_set', $config['key_set']);

        foreach ($this->subSources as $source) {
            $source->load($path, $container, $config);
        }
        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/openid_connect'));
        $loader->load('userinfo_scope_support.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'id_token';
    }

    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->validate()
                ->ifTrue(function ($config) {
                    return empty($config['default_signature_algorithm']);
                })
                ->thenInvalid('The option "default_signature_algorithm" must be set.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return empty($config['signature_algorithms']);
                })
                ->thenInvalid('The option "signature_algorithm" must contain at least one signature algorithm.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return !in_array($config['default_signature_algorithm'], $config['signature_algorithms']);
                })
                ->thenInvalid('The default signature algorithm must be in the supported signature algorithms.')
            ->end()
            ->validate()
                ->ifTrue(function ($config) {
                    return empty($config['key_set']);
                })
                ->thenInvalid('The option "key_set" must be set.')
            ->end()
            ->children()
                ->scalarNode('default_signature_algorithm')
                    ->info('Signature algorithm used if the client has not defined a preferred one. Recommended value is "RS256".')
                ->end()
                ->scalarNode('key_set')
                    ->info('Key set that contains a suitable signature key for the selected signature algorithms.')
                ->end()
                ->arrayNode('signature_algorithms')
                    ->info('Signature algorithm used to sign the ID Tokens.')
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
                ->integerNode('lifetime')
                    ->info('Lifetime of the ID Tokens (in seconds). If an access token is issued with the ID Token, the lifetime of the access token is used instead of this value.')
                    ->defaultValue(3600)
                    ->min(1)
                ->end()
            ->end();
        foreach ($this->subSources as $source) {
            $source->addConfiguration($node);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $sourceConfig
     */
    private function updateJoseBundleConfigurationForSigner(ContainerBuilder $container, array $sourceConfig)
    {
        ConfigurationHelper::addSigner($container, $this->name(), $sourceConfig['signature_algorithms'], false, false);
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
