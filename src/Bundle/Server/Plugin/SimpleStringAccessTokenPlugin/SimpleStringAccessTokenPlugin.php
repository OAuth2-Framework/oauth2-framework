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

namespace OAuth2Framework\Bundle\Server\SimpleStringAccessTokenPlugin;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\SimpleStringAccessTokenPlugin\DependencyInjection\Compiler\SimpleStringAccessTokenConfigurationCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SimpleStringAccessTokenPlugin extends CommonPluginMethod implements BundlePlugin
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'simple_string_access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SimpleStringAccessTokenConfigurationCompilerPass());

        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping') => 'OAuth2Framework\Bundle\Server\SimpleStringAccessTokenPlugin\Model',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, ['oauth2_server.access_token.manager']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.yml');

        $parameters = [
            'oauth2_server.simple_string_access_token.token_manager' => ['type' => 'alias', 'path' => '[manager]'],
            'oauth2_server.simple_string_access_token.token_class' => ['type' => 'parameter', 'path' => '[class]'],
            'oauth2_server.simple_string_access_token.min_length' => ['type' => 'parameter', 'path' => '[min_length]'],
            'oauth2_server.simple_string_access_token.max_length' => ['type' => 'parameter', 'path' => '[max_length]'],
            'oauth2_server.simple_string_access_token.lifetime' => ['type' => 'parameter', 'path' => '[lifetime]'],
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
                    ->info('The minimum length of access token values produced by this bundle. Should be at least 40.')
                    ->defaultValue(40)
                    ->min(1)
                ->end()
                ->integerNode('max_length')
                    ->info('The maximum length of access token values produced by this bundle. Should be at least 50.')
                    ->defaultValue(50)
                    ->min(2)
                ->end()
                ->integerNode('lifetime')
                    ->info('The lifetime (in seconds) of an access token (default is 1800 seconds = 30 minutes).')
                    ->defaultValue(1800)
                    ->min(0)
                ->end()
                ->scalarNode('class')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return !class_exists($value);
                        })
                        ->thenInvalid('The class does not exist.')
                    ->end()
                    ->info('Access token class.')
                    ->isRequired()
                ->end()
                ->scalarNode('manager')
                    ->info('Access token manager.')
                    ->defaultValue('oauth2_server.simple_string_access_token.manager.default')
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
    }
}
