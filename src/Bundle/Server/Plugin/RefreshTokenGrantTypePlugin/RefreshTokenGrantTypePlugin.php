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

namespace OAuth2Framework\Bundle\Server\RefreshTokenGrantTypePlugin;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\RefreshTokenGrantTypePlugin\DependencyInjection\Compiler\RefreshTokenConfigurationCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class RefreshTokenGrantTypePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'refresh_token';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RefreshTokenConfigurationCompilerPass());

        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping') => 'OAuth2Framework\Bundle\Server\RefreshTokenGrantTypePlugin\Model',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, ['oauth2_server.refresh_token.manager']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        foreach (['services'] as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }

        $parameters = [
            'oauth2_server.refresh_token.token_manager' => ['type' => 'alias',     'path' => '[manager]'],
            'oauth2_server.refresh_token.token_class'   => ['type' => 'parameter', 'path' => '[class]'],
            'oauth2_server.refresh_token.min_length'    => ['type' => 'parameter', 'path' => '[min_length]'],
            'oauth2_server.refresh_token.max_length'    => ['type' => 'parameter', 'path' => '[max_length]'],
            'oauth2_server.refresh_token.lifetime'      => ['type' => 'parameter', 'path' => '[lifetime]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->isRequired()
            ->addDefaultsIfNotSet()
                ->validate()
                    ->ifTrue(function ($value) {
                        return $value['min_length'] >= $value['max_length'];
                    })
                    ->thenInvalid('The configuration option "min_length" must be lower than "max_length".')
                ->end()
            ->children()
                ->integerNode('min_length')
                    ->info('The minimum length of refresh token values produced by this bundle. Should be at least 20.')
                    ->defaultValue(20)
                    ->min(1)
                ->end()
                ->integerNode('max_length')
                    ->info('The maximum length of refresh token values produced by this bundle. Should be at least 30.')
                    ->defaultValue(30)
                    ->min(2)
                ->end()
                ->integerNode('lifetime')
                    ->info('The lifetime (in seconds) of a refresh token (default is 1209600 seconds = 14 days).')
                    ->defaultValue(1209600)
                    ->min(0)
                ->end()
                ->scalarNode('class')
                    ->info('Refresh token class.')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !class_exists($value);
                        })->thenInvalid('The class does not exist.')
                    ->end()
                    ->isRequired()
                ->end()
                ->scalarNode('manager')
                    ->info('Refresh token manager.')
                    ->defaultValue('oauth2_server.refresh_token.manager.default')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = current($container->getExtensionConfig('oauth2_server'));
        Assertion::keyExists($config, 'token_endpoint', 'The "TokenEndpointPlugin" must be enabled to use the "RefreshTokenGrantTypePlugin".');
    }
}
