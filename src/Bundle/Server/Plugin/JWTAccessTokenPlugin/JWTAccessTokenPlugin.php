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

namespace OAuth2Framework\Bundle\Server\JWTAccessTokenPlugin;

use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class JWTAccessTokenPlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'jwt_access_token';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.yml');

        $parameters = [
            'oauth2_server.jwt_access_token.manager.issuer' => ['type' => 'parameter', 'path' => '[issuer]'],
            'oauth2_server.jwt_access_token.manager.token_lifetime' => ['type' => 'parameter', 'path' => '[token_lifetime]'],
            'oauth2_server.jwt_access_token.manager.signature_algorithm' => ['type' => 'parameter', 'path' => '[signature_algorithm]'],
            'oauth2_server.jwt_access_token.manager.signature_key_set' => ['type' => 'alias', 'path' => '[signature_key_set]'],
            'oauth2_server.jwt_access_token.manager.key_encryption_algorithm' => ['type' => 'parameter', 'path' => '[key_encryption_algorithm]'],
            'oauth2_server.jwt_access_token.manager.content_encryption_algorithm' => ['type' => 'parameter', 'path' => '[content_encryption_algorithm]'],
            'oauth2_server.jwt_access_token.manager.claim_checkers' => ['type' => 'parameter', 'path' => '[claim_checkers]'],
            'oauth2_server.jwt_access_token.manager.header_checkers' => ['type' => 'parameter', 'path' => '[header_checkers]'],
            'oauth2_server.jwt_access_token.manager.key_encryption_key_set' => ['type' => 'alias', 'path' => '[key_encryption_key_set]'],
        ];

        $this->loadParameters($parameters, $pluginConfiguration, $container);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('token_lifetime')
                    ->info('The lifetime of the access token (in seconds). The default value is 1800 (30 minutes).')
                    ->min(1)
                    ->defaultValue(1800)
                ->end()
                ->scalarNode('issuer')
                    ->info('The issuer of the access token. Should be the Url of the authorization server.')
                    ->isRequired()
                ->end()
                ->scalarNode('key_encryption_algorithm')
                    ->info('The key encryption algorithm.')
                    ->isRequired()
                ->end()
                ->scalarNode('content_encryption_algorithm')
                    ->info('The content encryption algorithm.')
                    ->isRequired()
                ->end()
                ->scalarNode('key_encryption_key_set')
                    ->info('The key encryption key. Must be compatible with the key encryption algorithm.')
                    ->isRequired()
                ->end()
                ->scalarNode('signature_algorithm')
                    ->info('The signature algorithm used to sign all access tokens. Must be supported by the JWT Loader and JWT Creator.')
                    ->isRequired()
                ->end()
                ->scalarNode('signature_key_set')
                    ->info('The key used to sign all access tokens. Must be compatible with the algorithm used to sign.')
                    ->isRequired()
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
            ->end()
            ->isRequired();
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
        $bundle_config = current($container->getExtensionConfig('oauth2_server'))[$this->name()];

        $this->updateJoseBundleConfigurationForSigner($container, 'jwt_access_token', $bundle_config);
        $this->updateJoseBundleConfigurationForEncrypter($container, 'jwt_access_token', $bundle_config);
        $this->updateJoseBundleConfigurationForJWTCreator($container, 'jwt_access_token');
        $this->updateJoseBundleConfigurationForVerifier($container, 'jwt_access_token', $bundle_config);
        $this->updateJoseBundleConfigurationForDecrypter($container, 'jwt_access_token', $bundle_config);
        $this->updateJoseBundleConfigurationForChecker($container, 'jwt_access_token', $bundle_config);
        $this->updateJoseBundleConfigurationForJWTLoader($container, 'jwt_access_token');
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForSigner(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addSigner($container, $service_name, [$bundle_config['signature_algorithm']], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForEncrypter(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addEncrypter($container, $service_name, [$bundle_config['key_encryption_algorithm']], [$bundle_config['content_encryption_algorithm']], ['DEF'], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     */
    private function updateJoseBundleConfigurationForJWTCreator(ContainerBuilder $container, $service_name)
    {
        $encrypter = sprintf('jose.encrypter.%s', $service_name);
        ConfigurationHelper::addJWTCreator($container, $service_name, sprintf('jose.signer.%s', $service_name), $encrypter, false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addVerifier($container, $service_name, [$bundle_config['signature_algorithm']], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addDecrypter($container, $service_name, [$bundle_config['key_encryption_algorithm']], [$bundle_config['content_encryption_algorithm']], ['DEF'], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForChecker(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        $bundle_config['header_checkers'] = isset($bundle_config['header_checkers']) ? $bundle_config['header_checkers'] : [];
        $bundle_config['claim_checkers'] = isset($bundle_config['claim_checkers']) ? $bundle_config['claim_checkers'] : [];
        ConfigurationHelper::addChecker($container, $service_name, $bundle_config['header_checkers'], $bundle_config['claim_checkers'], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     */
    private function updateJoseBundleConfigurationForJWTLoader(ContainerBuilder $container, $service_name)
    {
        $decrypter = sprintf('jose.decrypter.%s', $service_name);
        ConfigurationHelper::addJWTLoader($container, $service_name, sprintf('jose.verifier.%s', $service_name), sprintf('jose.checker.%s', $service_name), $decrypter, false);
    }
}
