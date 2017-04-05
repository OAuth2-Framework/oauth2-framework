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

namespace OAuth2Framework\Bundle\Server\JWTBearerPlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\JWTBearerPlugin\DependencyInjection\Compiler\JWTBearerConfigurationCompilerPass;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JWTBearerPlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.key_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.content_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET = 'The parameter "encryption.key_set" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_SECRET_BASIC_REALM = 'The child node "realm" at path must be configured.';

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'jwt_bearer_grant_type';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new JWTBearerConfigurationCompilerPass());
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
            'oauth2_server.jwt_bearer_grant_type.issue_refresh_token' => ['type' => 'parameter', 'path' => '[issue_refresh_token]'],
            'oauth2_server.jwt_bearer_grant_type.encryption.enabled' => ['type' => 'parameter', 'path' => '[encryption][enabled]'],
        ];

        $accessor = PropertyAccess::createPropertyAccessor();
        if (true === $accessor->getValue($pluginConfiguration, '[encryption][enabled]')) {
            $parameters['oauth2_server.jwt_bearer_grant_type.encryption.key_set'] = ['type' => 'alias', 'path' => '[encryption][key_set]'];
            $parameters['oauth2_server.jwt_bearer_grant_type.encryption.required'] = ['type' => 'parameter', 'path' => '[encryption][required]'];
        }

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
            ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS)->end()
            ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('content_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS)->end()
            ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_set'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET)->end()
            ->children()
                ->booleanNode('issue_refresh_token')
                    ->info('A refresh token, if available, will be issued with the access token. This option is not recommended.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('signature_algorithms')
                    ->info('Supported signature algorithms.')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                    ->cannotBeEmpty()
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
                ->arrayNode('encryption')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->booleanNode('required')->defaultFalse()->end()
                        ->scalarNode('key_set')->defaultNull()->end()
                        ->arrayNode('key_encryption_algorithms')
                            ->info('Supported key encryption algorithms.')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                            ->treatNullLike([])
                        ->end()
                        ->arrayNode('content_encryption_algorithms')
                            ->info('Supported content encryption algorithms.')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                            ->treatNullLike([])
                        ->end()
                    ->end()
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
        Assertion::keyExists($config, 'token_endpoint', 'The "TokenEndpointPlugin" must be enabled to use the "JWTBearerPlugin".');

        $bundle_config = current($container->getExtensionConfig('oauth2_server'))[$this->name()];

        $this->updateJoseBundleConfigurationForVerifier($container, 'jwt_bearer_grant_type', $bundle_config);
        $this->updateJoseBundleConfigurationForDecrypter($container, 'jwt_bearer_grant_type', $bundle_config);
        $this->updateJoseBundleConfigurationForChecker($container, 'jwt_bearer_grant_type', $bundle_config);
        $this->updateJoseBundleConfigurationForJWTLoader($container, 'jwt_bearer_grant_type', $bundle_config);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addVerifier($container, $service_name, $bundle_config['signature_algorithms'], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForDecrypter(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        if (true === $bundle_config['encryption']['enabled']) {
            ConfigurationHelper::addDecrypter($container, $service_name, $bundle_config['encryption']['key_encryption_algorithms'], $bundle_config['encryption']['content_encryption_algorithms'], ['DEF'], false);
        }
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForChecker(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addChecker($container, $service_name, $bundle_config['header_checkers'], $bundle_config['claim_checkers'], false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     * @param array                                                   $bundle_config
     */
    private function updateJoseBundleConfigurationForJWTLoader(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        $decrypter = null;
        if (true === $bundle_config['encryption']['enabled']) {
            $decrypter = sprintf('jose.decrypter.%s', $service_name);
        }
        ConfigurationHelper::addJWTLoader($container, $service_name, sprintf('jose.verifier.%s', $service_name), sprintf('jose.checker.%s', $service_name), $decrypter, false);
    }

    /**
     * @param string $parameter
     *
     * @return \Closure
     */
    private function isClientAssertionEncryptionParameterInvalid($parameter)
    {
        return function($data) use ($parameter) {
            if (false === $data['encryption']['enabled']) {
                return false;
            }

            return empty($data['encryption'][$parameter]);
        };
    }
}
