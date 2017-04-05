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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ClientRegistrationSoftwareStatementSource extends ActionableSource
{
    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach (['required', 'allowed_signature_algorithms'] as $k) {
            $container->setParameter($path.'.'.$k, $config[$k]);
        }
        $container->setAlias($path.'.key_set', $config['key_set']);

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('client_registration_software_statement.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'software_statement';
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
                    return true === $config['enabled'] && empty($config['key_set']);
                })
                ->thenInvalid('The option "key_set" must be set.')
            ->end()
            ->validate()
            ->ifTrue(function($config) {
                return true === $config['enabled'] && empty($config['allowed_signature_algorithms']);
            })
            ->thenInvalid('At least one signature algorithm must be set.')
            ->end()
            ->children()
                ->booleanNode('required')->defaultFalse()->end()
                ->scalarNode('key_set')->end()
                ->arrayNode('allowed_signature_algorithms')
                    ->info('Signature algorithms allowed for the software statements. The algorithm "none" should not be used.')
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
        $currentPath = $path.'['.$this->name().']';
        $accessor = PropertyAccess::createPropertyAccessor();
        $sourceConfig = $accessor->getValue($bundleConfig, $currentPath);

        if (true === $sourceConfig['enabled']) {
            $this->updateJoseBundleConfigurationForVerifier($container, ['signature_algorithms' => $sourceConfig['allowed_signature_algorithms']]);
            $this->updateJoseBundleConfigurationForChecker($container, ['header_checkers' => [], 'claim_checkers' => []]);
            $this->updateJoseBundleConfigurationForJWTLoader($container);
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
     */
    private function updateJoseBundleConfigurationForJWTLoader(ContainerBuilder $container)
    {
        ConfigurationHelper::addJWTLoader($container, $this->name(), sprintf('jose.verifier.%s', $this->name()), sprintf('jose.checker.%s', $this->name()), null, false);
    }
}
