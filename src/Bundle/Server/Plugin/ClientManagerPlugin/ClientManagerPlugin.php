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

namespace OAuth2Framework\Bundle\Server\ClientManagerPlugin;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\AssertionJwtMetadataCompilerPass;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\ClientAssertionJWTEncryptionSupportConfigurationCompilerPass;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\ClientConfigurationRouteCompilerPass;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\ClientManagementCompilerPass;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\ClientRegistrationRouteCompilerPass;
use OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler\ClientRuleCompilerPass;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ClientManagerPlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    const ERROR_EMPTY_CLIENT_ASSERTION_SIGNATURE_ALGORITHMS = 'The parameter "signature_algorithms" must be set when the "client_assertion_jwt" authentication method is enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.key_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.content_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET = 'The parameter "encryption.key_set" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_SECRET_BASIC_REALM = 'The child node "realm" at path must be configured.';
    const ERROR_INVALID_REGISTRATION_PATH = 'The parameter "registration_path" must be set.';
    const ERROR_INVALID_CONFIGURATION_PATH = 'The parameter "configuration_path" must be set.';
    const ERROR_INVALID_INITIAL_ACCESS_TOKEN_CLASS = 'The Initial Access Token class is not set or does not exist.';
    const ERROR_INVALID_INITIAL_ACCESS_TOKEN_MANAGER = 'The Initial Access Token manager must be set.';
    const ERROR_INVALID_SOFTWARE_STATEMENT_KEY_SET = 'The Software Statement key set must be set.';
    const ERROR_INVALID_SOFTWARE_STATEMENT_ALGORITHM = 'The Software Statement algorithm must be set.';

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'client_manager';
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping') => 'OAuth2Framework\Bundle\Server\ClientManagerPlugin\Model',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, []));
        }

        $container->addCompilerPass(new AssertionJwtMetadataCompilerPass());
        $container->addCompilerPass(new ClientAssertionJWTEncryptionSupportConfigurationCompilerPass());
        $container->addCompilerPass(new ClientManagementCompilerPass());
        $container->addCompilerPass(new ClientRegistrationRouteCompilerPass());
        $container->addCompilerPass(new ClientConfigurationRouteCompilerPass());
        $container->addCompilerPass(new ClientRuleCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = ['services'];

        $parameters = [
            'oauth2_server.client_manager'       => ['type' => 'alias', 'path' => '[manager]'],
            'oauth2_server.client_manager.class' => ['type' => 'parameter', 'path' => '[class]'],
        ];

        if (true === $pluginConfiguration['token_endpoint_auth_methods']['none']) {
            $files[] = 'token_endpoint_auth_method.none';
        }
        if (true === $pluginConfiguration['token_endpoint_auth_methods']['client_secret_basic']['enabled']) {
            $files[] = 'token_endpoint_auth_method.client_secret_basic';
            $parameters['oauth2_server.token_endpoint_auth_method.client_secret_basic.realm'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_secret_basic][realm]'];
        }
        if (true === $pluginConfiguration['token_endpoint_auth_methods']['client_secret_post']) {
            $files[] = 'token_endpoint_auth_method.client_secret_post';
        }
        if (true === $pluginConfiguration['token_endpoint_auth_methods']['client_assertion_jwt']['enabled']) {
            $files[] = 'token_endpoint_auth_method.client_assertion_jwt';
            $accessor = PropertyAccess::createPropertyAccessor();

            $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.signature_algorithms'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][signature_algorithms]'];
            $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.claim_checkers'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][claim_checkers]'];
            $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.header_checkers'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][header_checkers]'];

            $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.enabled'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][encryption][enabled]'];
            if (true === $accessor->getValue($pluginConfiguration, '[token_endpoint_auth_methods][client_assertion_jwt][encryption][enabled]')) {
                $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.key_encryption_algorithms'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][encryption][key_encryption_algorithms]'];
                $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.content_encryption_algorithms'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][encryption][content_encryption_algorithms]'];
                $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.required'] = ['type' => 'parameter', 'path' => '[token_endpoint_auth_methods][client_assertion_jwt][encryption][required]'];
                $parameters['oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.key_set'] = ['type' => 'alias',     'path' => '[token_endpoint_auth_methods][client_assertion_jwt][encryption][key_set]'];
            }
        }

        $parameters['oauth2_server.client_manager.management.enabled'] = ['type' => 'parameter', 'path' => '[management][enabled]'];
        if (true === $pluginConfiguration['management']['enabled']) {
            $files[] = 'management_endpoint';
            $parameters['oauth2_server.client_manager.management.registration_path'] = ['type' => 'parameter', 'path' => '[management][registration_path]'];
            $parameters['oauth2_server.client_manager.management.configuration_path'] = ['type' => 'parameter', 'path' => '[management][configuration_path]'];

            $parameters['oauth2_server.initial_access_token.enabled'] = ['type' => 'parameter', 'path' => '[management][initial_access_token][enabled]'];
            if (true === $pluginConfiguration['management']['initial_access_token']['enabled']) {
                $files[] = 'initial_access_token';
                $parameters['oauth2_server.initial_access_token.required'] = ['type' => 'parameter', 'path' => '[management][initial_access_token][required]'];
                $parameters['oauth2_server.initial_access_token.class'] = ['type' => 'parameter', 'path' => '[management][initial_access_token][class]'];
                $parameters['oauth2_server.initial_access_token.manager'] = ['type' => 'alias', 'path' => '[management][initial_access_token][manager]'];
            }

            $parameters['oauth2_server.software_statement.enabled'] = ['type' => 'parameter', 'path' => '[management][software_statement][enabled]'];
            if (true === $pluginConfiguration['management']['software_statement']['enabled']) {
                $parameters['oauth2_server.software_statement.required'] = ['type' => 'parameter', 'path' => '[management][software_statement][required]'];
                $parameters['oauth2_server.software_statement.key_set'] = ['type' => 'alias', 'path' => '[management][software_statement][key_set]'];
                $parameters['oauth2_server.software_statement.signature_algorithms'] = ['type' => 'parameter', 'path' => '[management][software_statement][signature_algorithms]'];
            }
        }

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
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
            ->children()
                ->scalarNode('class')
                    ->info('The Client class.')
                    ->isRequired()
                    ->validate()
                    ->ifTrue(function ($value) {
                        return !class_exists($value);
                    })
                    ->thenInvalid('The class does not exist.')
                    ->end()
                ->end()
                ->scalarNode('manager')
                    ->info('The Client manager.')
                    ->defaultValue('oauth2_server.client_manager.default')
                ->end()
            ->end();
        $this->addTokenEndpointAuthMethodsSection($pluginNode);
        $this->addManagementSection($pluginNode);
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addTokenEndpointAuthMethodsSection(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode('token_endpoint_auth_methods')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('none')
                        ->info('The "none" authentication method. Should be enabled to allow public clients to be authenticated against the endpoints other than the authorization one (e.g. Token Endpoint, Token Introspection Endpoint...)')
                        ->defaultTrue()
                    ->end()
                    ->arrayNode('client_secret_basic')
                        ->info('The "client_secret_basic" authentication method. Must be enabled to allow confidential clients with a secret to be authenticated using the authorization header. This method is recommended.')
                        ->validate()->ifTrue($this->isClientSecretBasicRealmInvalid())->thenInvalid(self::ERROR_EMPTY_CLIENT_SECRET_BASIC_REALM)->end()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')
                                ->info('If true, the "client_secret_basic" authentication method is enabled.')
                                ->defaultTrue()
                            ->end()
                            ->scalarNode('realm')
                                ->info('The realm display during the authentication process.')
                            ->end()
                        ->end()
                    ->end()
                    ->booleanNode('client_secret_post')
                        ->info('The "client_secret_post" authentication method. Should be enabled to allow confidential clients with a secret to be authenticated using the request body parameters. This method is not recommended.')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('client_assertion_jwt')
                        ->validate()->ifTrue($this->areClientAssertionSignatureAlgorithmsInvalid())->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_SIGNATURE_ALGORITHMS)->end()
                        ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS)->end()
                        ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('content_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS)->end()
                        ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_set'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET)->end()
                        ->info('The "client_secret_jwt" and "private_key_jwt" authentication methods. Should be enabled to allow confidential clients with a secret or keys to be authenticated using the JWT assertions. These methods are recommended, but require cryptographic knowledge.')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')->defaultFalse()->end()
                            ->arrayNode('signature_algorithms')
                                ->info('Supported signature algorithms.')
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
                            ->arrayNode('encryption')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->booleanNode('enabled')
                                        ->defaultFalse()
                                        ->info('If true, encrypted assertion support is enabled. Key set and encryption algorithms must be set..')
                                    ->end()
                                    ->booleanNode('required')
                                        ->defaultFalse()
                                        ->info('If true, assertion must be encrypted by clients.')
                                    ->end()
                                    ->scalarNode('key_set')
                                        ->info('Key set used to decrypt the assertion.')
                                        ->defaultNull()
                                    ->end()
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
                        ->end()
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addManagementSection(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode('management')
                ->validate()->ifTrue($this->isRegistrationPathValid())->thenInvalid(self::ERROR_INVALID_REGISTRATION_PATH)->end()
                ->validate()->ifTrue($this->isConfigurationPathValid())->thenInvalid(self::ERROR_INVALID_CONFIGURATION_PATH)->end()
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')
                        ->info('If true, Client Registration and Client Configuration Endpoint will be enabled.')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('registration_path')
                        ->info('The path to the client registration endpoint.')
                        ->defaultValue('/client/register')
                    ->end()
                    ->scalarNode('configuration_path')
                        ->info('The path to the client configuration endpoint. It must contain the {client_id} parameters.')
                        ->defaultValue('/client/register/{client_id}')
                    ->end()
                    ->arrayNode('initial_access_token')
                        ->validate()->ifTrue($this->isInitialAccessTokenClassValid())->thenInvalid(self::ERROR_INVALID_INITIAL_ACCESS_TOKEN_CLASS)->end()
                        ->validate()->ifTrue($this->isInitialAccessTokenManagerValid())->thenInvalid(self::ERROR_INVALID_INITIAL_ACCESS_TOKEN_MANAGER)->end()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')
                                ->info('If true, Initial Access Token support is enabled.')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('required')
                                ->info('If true, an Initial Access Token is required to register a new client.')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('class')
                                ->info('Initial Access Token class.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('manager')
                                ->info('Initial Access Token manager.')
                                ->defaultValue('oauth2_server.initial_access_token.manager.default')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('software_statement')
                        ->validate()->ifTrue($this->isSoftwareStatementKeySetValid())->thenInvalid(self::ERROR_INVALID_SOFTWARE_STATEMENT_KEY_SET)->end()
                        ->validate()->ifTrue($this->isSoftwareStatementAlgorithmValid())->thenInvalid(self::ERROR_INVALID_SOFTWARE_STATEMENT_ALGORITHM)->end()
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')
                                ->info('If true, Software Statement support is enabled.')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('required')
                                ->info('If true, an Software Statement is required to register a new client.')
                                ->defaultFalse()
                            ->end()
                            ->scalarNode('key_set')
                                ->info('Signature key set.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('signature_algorithm')
                                ->info('Supported signature algorithm.')
                                ->defaultNull()
                            ->end()
                        ->end()
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
        $bundle_config = current($container->getExtensionConfig('oauth2_server'))[$this->name()];

        if (true === $bundle_config['management']['enabled']) {
            $plugins = $container->getExtensionConfig('oauth2_server');
            if (!empty($plugins)) {
                $plugins = array_shift($plugins);
            }
            Assertion::keyExists($plugins, 'bearer_token', 'The plugin "BearerTokenPlugin" must be enabled to use the client management endpoints.');

            if (true === $bundle_config['management']['software_statement']['enabled']) {
                $this->updateJoseBundleConfigurationForVerifier($container, 'oauth2_server_software_statement', ['signature_algorithms' => [$bundle_config['management']['software_statement']['signature_algorithm']]]);
                $this->updateJoseBundleConfigurationForChecker($container, 'oauth2_server_software_statement', ['header_checkers' => [], 'claim_checkers' => []]);
                $this->updateJoseBundleConfigurationForJWTLoader($container, 'oauth2_server_software_statement', ['encryption' => ['enabled' => false]]);
            }
        }

        if (true === $bundle_config['token_endpoint_auth_methods']['client_assertion_jwt']['enabled']) {
            $this->updateJoseBundleConfigurationForVerifier($container, 'client_manager_client_assertion_jwt', $bundle_config['token_endpoint_auth_methods']['client_assertion_jwt']);
            $this->updateJoseBundleConfigurationForDecrypter($container, 'client_manager_client_assertion_jwt', $bundle_config['token_endpoint_auth_methods']['client_assertion_jwt']);
            $this->updateJoseBundleConfigurationForChecker($container, 'client_manager_client_assertion_jwt', $bundle_config['token_endpoint_auth_methods']['client_assertion_jwt']);
            $this->updateJoseBundleConfigurationForJWTLoader($container, 'client_manager_client_assertion_jwt', $bundle_config['token_endpoint_auth_methods']['client_assertion_jwt']);
        }
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
     * @return \Closure
     */
    private function areClientAssertionSignatureAlgorithmsInvalid()
    {
        return function ($data) {
            if (false === $data['enabled']) {
                return false;
            }

            return empty($data['signature_algorithms']);
        };
    }

    /**
     * @param string $parameter
     *
     * @return \Closure
     */
    private function isClientAssertionEncryptionParameterInvalid($parameter)
    {
        return function ($data) use ($parameter) {
            if (false === $data['encryption']['enabled']) {
                return false;
            }

            return empty($data['encryption'][$parameter]);
        };
    }

    /**
     * @return \Closure
     */
    private function isClientSecretBasicRealmInvalid()
    {
        return function ($data) {
            if (false === $data['enabled']) {
                return false;
            }

            return empty($data['realm']);
        };
    }

    /**
     * @return \Closure
     */
    private function isRegistrationPathValid()
    {
        return function ($data) {
            return true === $data['enabled'] && empty($data['registration_path']);
        };
    }

    /**
     * @return \Closure
     */
    private function isConfigurationPathValid()
    {
        return function ($data) {
            return true === $data['enabled'] && empty($data['configuration_path']);
        };
    }

    /**
     * @return \Closure
     */
    private function isInitialAccessTokenClassValid()
    {
        return function ($data) {
            return true === $data['enabled'] && (empty($data['class']) || !class_exists($data['class']));
        };
    }

    /**
     * @return \Closure
     */
    private function isInitialAccessTokenManagerValid()
    {
        return function ($data) {
            return true === $data['enabled'] && empty($data['class']);
        };
    }

    /**
     * @return \Closure
     */
    private function isSoftwareStatementKeySetValid()
    {
        return function ($data) {
            return true === $data['enabled'] && empty($data['key_set']);
        };
    }

    /**
     * @return \Closure
     */
    private function isSoftwareStatementAlgorithmValid()
    {
        return function ($data) {
            return true === $data['enabled'] && empty($data['signature_algorithm']);
        };
    }
}
