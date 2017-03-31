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

namespace OAuth2Framework\Bundle\Server\AuthorizationEndpointPlugin;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AuthorizationEndpointPlugin extends CommonPluginMethod implements BundlePlugin, DependencyInjection\Extension\PrependExtensionInterface
{
    const ERROR_EMPTY_CLIENT_ASSERTION_SIGNATURE_ALGORITHMS = 'The parameter "signature_algorithms" must be set when the "client_assertion_jwt" authentication method is enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.key_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS = 'The parameter "encryption.content_encryption_algorithms" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET = 'The parameter "encryption.key_set" must be set when "client_assertion_jwt" authentication method and encryption support are enabled.';
    const ERROR_EMPTY_CLIENT_SECRET_BASIC_REALM = 'The child node "realm" at path must be configured.';

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'authorization_endpoint';
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
                ->scalarNode('login_route_name')
                    ->info('The name of the login route. Will be converted into URL and used to redirect the user if not logged in. If you use "FOSUserBundle", the route name should be "fos_user_security_login".')
                    ->isRequired()
                ->end()
                ->arrayNode('login_route_parameters')
                    ->info('Parameters associated to the login route (if needed).')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->scalarNode('user_account_manager')
                    ->info('The user account manager.')
                    ->isRequired()
                ->end()
                ->scalarNode('template')
                    ->info('The consent page template.')
                    ->cannotBeEmpty()
                    ->defaultValue('@OAuth2FrameworkServerBundle/authorization/authorization.html.twig')
                ->end()
                ->booleanNode('allow_scope_selection')
                    ->info('If true, resource owners will be able to select the scope on the consent page. This option is useless if the "ScopeManagerPlugin" is not enabled.')
                    ->defaultFalse()
                ->end()
                ->scalarNode('path')
                    ->info('The path to the authorization endpoint.')
                    ->defaultValue('/oauth/v2/authorize')
                ->end()
            ->end();
        $this->addFormSection($pluginNode);
        $this->addOptionSection($pluginNode);
        $this->addAuthorizationRequestSection($pluginNode);
        $this->addPreConfiguredAuthorizationSection($pluginNode);
    }

    private function addFormSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('form')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')
                            ->info('The form type.')
                            ->defaultValue('OAuth2Framework\Bundle\Server\Form\Type\AuthorizationType')
                        ->end()
                        ->scalarNode('handler')
                            ->info('The form handler.')
                            ->defaultValue('oauth2_server.authorization_endpoint.form_handler.default')
                        ->end()
                            ->scalarNode('name')
                            ->info('The form name.')
                            ->defaultValue('oauth2_server_authorization_form')

                        ->end()
                        ->arrayNode('validation_groups')
                            ->info('Validation group associated to the authorization form.')
                            ->prototype('scalar')->end()
                            ->defaultValue(['Authorize', 'Default'])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addAuthorizationRequestSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('request_object')
                    ->validate()->ifTrue($this->areClientAssertionSignatureAlgorithmsInvalid())->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_SIGNATURE_ALGORITHMS)->end()
                    ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_ENCRYPTION_ALGORITHMS)->end()
                    ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('content_encryption_algorithms'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_CONTENT_ENCRYPTION_ALGORITHMS)->end()
                    ->validate()->ifTrue($this->isClientAssertionEncryptionParameterInvalid('key_set'))->thenInvalid(self::ERROR_EMPTY_CLIENT_ASSERTION_KEY_SET)->end()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultFalse()->end()
                        ->booleanNode('allow_unsecured_connections')->defaultFalse()->end()
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
                            ->treatNullLike(['exp', 'iat', 'nbf', 'authorization_endpoint_aud'])
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
                        ->arrayNode('reference')
                            ->children()
                                ->booleanNode('enabled')
                                    ->info('If true, request object Uris will be allowed. This option is useless if request object support is not enabled.')
                                    ->defaultFalse()
                                ->end()
                                ->booleanNode('uris_registration_required')
                                    ->info('If true, allowed request object Uris must be registered by the client. Request objects by reference must be enabled. Default value is true (highly recommended).')
                                    ->defaultTrue()
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
    private function addOptionSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('option')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enforce_secured_redirect_uri')
                            ->info('If true, the "redirect_uri" parameter must be a secured URL (https scheme).')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('enforce_redirect_uri_storage')
                            ->info('If true, all clients must register at least one redirect URI.')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('enforce_state')
                            ->info('If true, the "state" parameter is mandatory (highly recommended).')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('allow_response_mode_parameter')
                            ->info('If true, the "response_mode" parameter is allowed (required if the "FormPostResponseModePlugin" is enabled).')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addPreConfiguredAuthorizationSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('pre_configured_authorization')
                    ->validate()
                        ->ifTrue(function ($value) {
                            if (false === $value['enabled']) {
                                return false;
                            }

                            return empty($value['class']) || !class_exists($value['class']);
                        })
                        ->thenInvalid('The class is not set or does not exist.')
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('If true, the pre-configured authorization feature will be enabled.')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('class')
                            ->info('The class.')
                        ->end()
                        ->scalarNode('manager')
                            ->info('The manager.')
                            ->defaultValue('oauth2_server.authorization_endpoint.pre_configured_authorization.manager.default')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, DependencyInjection\ContainerBuilder $container)
    {
        $files = $this->initConfigurationParametersAndAliases($pluginConfiguration, $container);
        $this->loadFiles($container, $files);
    }

    /**
     * @param array                                                   $pluginConfiguration
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @return string[]
     */
    private function initConfigurationParametersAndAliases(array $pluginConfiguration, DependencyInjection\ContainerBuilder $container)
    {
        $files = [];
        $accessor = PropertyAccess::createPropertyAccessor();
        $parameters = [
            'oauth2_server.authorization_endpoint.form_handler'                          => ['type' => 'alias',     'path' => '[form][handler]'],
            'oauth2_server.authorization_endpoint.user_account_manager'                  => ['type' => 'alias',     'path' => '[user_account_manager]'],
            'oauth2_server.authorization_endpoint.template'                              => ['type' => 'parameter', 'path' => '[template]'],
            'oauth2_server.authorization_endpoint.allow_scope_selection'                 => ['type' => 'parameter', 'path' => '[allow_scope_selection]'],
            'oauth2_server.authorization_endpoint.type'                                  => ['type' => 'parameter', 'path' => '[form][type]'],
            'oauth2_server.authorization_endpoint.name'                                  => ['type' => 'parameter', 'path' => '[form][name]'],
            'oauth2_server.authorization_endpoint.validation_groups'                     => ['type' => 'parameter', 'path' => '[form][validation_groups]'],
            'oauth2_server.authorization_endpoint.login_route_name'                      => ['type' => 'parameter', 'path' => '[login_route_name]'],
            'oauth2_server.authorization_endpoint.login_route_parameters'                => ['type' => 'parameter', 'path' => '[login_route_parameters]'],
            'oauth2_server.authorization_endpoint.path'                                  => ['type' => 'parameter', 'path' => '[path]'],
            'oauth2_server.authorization_endpoint.option.allow_response_mode_parameter'  => ['type' => 'parameter', 'path' => '[option][allow_response_mode_parameter]'],
            'oauth2_server.authorization_endpoint.option.enforce_secured_redirect_uri'   => ['type' => 'parameter', 'path' => '[option][enforce_secured_redirect_uri]'],
            'oauth2_server.authorization_endpoint.option.enforce_redirect_uri_storage'   => ['type' => 'parameter', 'path' => '[option][enforce_secured_redirect_uri]'],
            'oauth2_server.authorization_endpoint.option.enforce_state'                  => ['type' => 'parameter', 'path' => '[option][enforce_state]'],
        ];

        $parameters['oauth2_server.authorization_request_loader.request_object.enabled'] = ['type' => 'parameter', 'path' => '[request_object][enabled]'];
        if (true === $accessor->getValue($pluginConfiguration, '[request_object][enabled]')) {
            $files[] = 'jwt.checkers';
            $parameters['oauth2_server.authorization_request_loader.request_object.allow_unsecured_connections'] = ['type' => 'parameter', 'path' => '[request_object][allow_unsecured_connections]'];
            $parameters['oauth2_server.authorization_request_loader.request_object.signature_algorithms'] = ['type' => 'parameter', 'path' => '[request_object][signature_algorithms]'];
            $parameters['oauth2_server.authorization_request_loader.request_object.claim_checkers'] = ['type' => 'parameter', 'path' => '[request_object][claim_checkers]'];
            $parameters['oauth2_server.authorization_request_loader.request_object.header_checkers'] = ['type' => 'parameter', 'path' => '[request_object][header_checkers]'];

            $parameters['oauth2_server.authorization_request_loader.request_object.encryption.enabled'] = ['type' => 'parameter', 'path' => '[request_object][encryption][enabled]'];
            if (true === $accessor->getValue($pluginConfiguration, '[request_object][encryption][enabled]')) {
                $parameters['oauth2_server.authorization_request_loader.request_object.encryption.key_encryption_algorithms'] = ['type' => 'parameter', 'path' => '[request_object][encryption][key_encryption_algorithms]'];
                $parameters['oauth2_server.authorization_request_loader.request_object.encryption.content_encryption_algorithms'] = ['type' => 'parameter', 'path' => '[request_object][encryption][content_encryption_algorithms]'];
                $parameters['oauth2_server.authorization_request_loader.request_object.encryption.required'] = ['type' => 'parameter', 'path' => '[request_object][encryption][required]'];
                $parameters['oauth2_server.authorization_request_loader.request_object.encryption.key_set'] = ['type' => 'alias',     'path' => '[request_object][encryption][key_set]'];
            }

            $parameters['oauth2_server.authorization_request_loader.request_object.reference_enabled'] = ['type' => 'parameter', 'path' => '[request_object][reference][enabled]'];
            $parameters['oauth2_server.authorization_request_loader.request_object.reference_uris_registration_required'] = ['type' => 'parameter', 'path' => '[request_object][reference][uris_registration_required]'];
        }

        if (true === $accessor->getValue($pluginConfiguration, '[pre_configured_authorization][enabled]')) {
            $files[] = 'pre_configured_authorization';
            $files[] = 'extensions/pre_configured_authorizations';
            $parameters['oauth2_server.authorization_endpoint.pre_configured_authorization.class'] = ['type' => 'parameter', 'path' => '[pre_configured_authorization][class]'];
            $parameters['oauth2_server.authorization_endpoint.pre_configured_authorization.manager'] = ['type' => 'alias', 'path' => '[pre_configured_authorization][manager]'];
        }

        $this->loadParameters($parameters, $pluginConfiguration, $container);

        return $files;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string[]                                                $files
     */
    private function loadFiles(DependencyInjection\ContainerBuilder $container, array $files)
    {
        $loader = new DependencyInjection\Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = array_merge(
            [
                'authorization.endpoint',
                'authorization.factory',
                'authorization.form',
                'response_modes',
                'parameter.checker',
            ],
            $files
        );

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(DependencyInjection\ContainerBuilder $container)
    {
        $mappings = [
            realpath(__DIR__.'/Resources/config/doctrine-mapping/library') => 'OAuth2Framework\Component\Server\Endpoint\Authorization\PreConfiguredAuthorization',
            realpath(__DIR__.'/Resources/config/doctrine-mapping/bundle')  => 'OAuth2Framework\Bundle\Server\Model',
        ];
        if (class_exists('Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createYamlMappingDriver($mappings, []));
        }

        $container->addCompilerPass(new Compiler\AuthorizationEndpointMetadataCompilerPass());
        $container->addCompilerPass(new Compiler\AuthorizationFactoryMetadataCompilerPass());
        $container->addCompilerPass(new Compiler\AuthorizationFactoryCompilerPass());
        $container->addCompilerPass(new Compiler\AuthorizationRequestLoaderCompilerPass());
        $container->addCompilerPass(new Compiler\ParameterCheckerCompilerPass());
        $container->addCompilerPass(new Compiler\AuthorizationEndpointExtensionCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(DependencyInjection\ContainerInterface $container)
    {
        $container->get('twig.loader')->addPath(__DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views', 'OAuth2FrameworkServerBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = current($container->getExtensionConfig('oauth2_server'));
        if (array_key_exists('token_endpoint', $config)) {
            foreach (['user_account_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        }
        $container->prependExtensionConfig('oauth2_server', $config);

        $bundles = $container->getParameter('kernel.bundles');
        Assertion::keyExists($bundles, 'SpomkyLabsJoseBundle', 'The "Spomky-Labs/JoseBundle" must be enabled.');

        $bundle_config = current($container->getExtensionConfig('oauth2_server'))[$this->name()];

        if (true === $bundle_config['request_object']['enabled']) {
            $this->updateJoseBundleConfigurationForVerifier($container, 'authorization_endpoint_authorization_request', $bundle_config['request_object']);
            $this->updateJoseBundleConfigurationForDecrypter($container, 'authorization_endpoint_authorization_request', $bundle_config['request_object']);
            $this->updateJoseBundleConfigurationForChecker($container, 'authorization_endpoint_authorization_request', $bundle_config['request_object']);
            $this->updateJoseBundleConfigurationForJWTLoader($container, 'authorization_endpoint_authorization_request', $bundle_config['request_object']);
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
}
