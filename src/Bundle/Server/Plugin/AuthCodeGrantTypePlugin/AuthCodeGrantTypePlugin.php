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

namespace OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin\DependencyInjection\Compiler\AuthCodeGrantTypeConfigurationCompilerPass;
use OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin\DependencyInjection\Compiler\AuthCodeManagerConfigurationCompilerPass;
use OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin\DependencyInjection\Compiler\PKCEMethodCompilerPass;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AuthCodeGrantTypePlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'auth_code_grant_type';
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
                ->ifTrue(function($value) {
                    return $value['min_length'] >= $value['max_length'];
                })
                ->thenInvalid('The configuration option "min_length" must be lower than "max_length".')
            ->end()
            ->children()
                ->scalarNode('class')
                    ->info('Authorization Code class.')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function($value) {
                            return !class_exists($value);
                        })
                        ->thenInvalid('The class does not exist.')
                    ->end()
                ->end()
                ->scalarNode('manager')
                    ->info('Authorization Code manager.')
                    ->defaultValue('oauth2_server.auth_code.manager.default')
                ->end()
                ->integerNode('min_length')
                    ->info('The minimum length of Authorization Code values produced by this bundle. Should be at least 20.')
                    ->defaultValue(20)
                    ->min(1)
                ->end()
                ->integerNode('max_length')
                    ->info('The maximum length of Authorization Code values produced by this bundle. Should be at least 30.')
                    ->defaultValue(30)
                    ->min(2)
                ->end()
                ->integerNode('lifetime')
                    ->info('The lifetime (in seconds) of an Authorization Code. Should be less than 1 minute (default is 30 seconds).')
                    ->defaultValue(30)
                    ->min(1)
                ->end()
                ->booleanNode('enforce_pkce')
                    ->info('Enforce Proof Key for token exchange (PKCE) for non-confidential clients (see RFC7636). This option is useless if the option "allow_public_clients" is set to "false".')
                    ->defaultTrue()
                ->end()
                ->booleanNode('allow_public_clients')
                    ->info('If true, public clients are allowed to issue access tokens using this grant type.')
                    ->defaultFalse()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $this->initConfigurationParametersAndAliases($pluginConfiguration, $container);
        $this->loadFiles($container);
    }

    /**
     * @param array                                                   $pluginConfiguration
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function initConfigurationParametersAndAliases(array $pluginConfiguration, ContainerBuilder $container)
    {
        $parameters = [
            'oauth2_server.auth_code.class' => ['type' => 'parameter', 'path' => '[class]'],
            'oauth2_server.auth_code.min_length' => ['type' => 'parameter', 'path' => '[min_length]'],
            'oauth2_server.auth_code.max_length' => ['type' => 'parameter', 'path' => '[max_length]'],
            'oauth2_server.auth_code.lifetime' => ['type' => 'parameter', 'path' => '[lifetime]'],
            'oauth2_server.auth_code.enforce_pkce' => ['type' => 'parameter', 'path' => '[enforce_pkce]'],
            'oauth2_server.auth_code.allow_public_clients' => ['type' => 'parameter', 'path' => '[allow_public_clients]'],
            'oauth2_server.auth_code.manager' => ['type' => 'alias', 'path' => '[manager]'],
        ];
        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    private function loadFiles(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = [
            'services',
            'manager',
            'pkce_methods',
        ];
        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AuthCodeManagerConfigurationCompilerPass());
        $container->addCompilerPass(new AuthCodeGrantTypeConfigurationCompilerPass());
        $container->addCompilerPass(new PKCEMethodCompilerPass());

        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping') => 'OAuth2Framework\Bundle\Server\AuthCodeGrantTypePlugin\Model',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, []));
        }
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
        Assertion::keyExists($config, 'token_endpoint', 'The "TokenEndpointPlugin" must be enabled to use the "AuthCodeGrantTypePlugin".');
        Assertion::keyExists($config, 'authorization_endpoint', 'The "AuthorizationEndpointPlugin" must be enabled to use the "AuthCodeGrantTypePlugin".');
    }
}
