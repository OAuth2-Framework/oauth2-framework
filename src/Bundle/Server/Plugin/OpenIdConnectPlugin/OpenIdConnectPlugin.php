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

namespace OAuth2Framework\Bundle\Server\OpenIdConnectPlugin;

use Assert\Assertion;
use Matthias\BundlePlugins\BundlePlugin;
use OAuth2Framework\Bundle\Server\CommonPluginMethod;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\ClaimSourceCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\IdTokenMetadataCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\IssuerDiscoveryCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\JwksUriMetadataCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\MetadataRouteCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\PairwiseSubjectIdentifierCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\SessionIFrameRouteCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\UserInfoEndpointSignatureSupportCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\UserInfoScopeSupportCompilerPass;
use OAuth2Framework\Bundle\Server\OpenIdConnectPlugin\DependencyInjection\Compiler\UserinfoRouteCompilerPass;
use SpomkyLabs\JoseBundle\Helper\ConfigurationHelper;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class OpenIdConnectPlugin extends CommonPluginMethod implements BundlePlugin, PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return 'openid_connect';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('user_account_manager')
                    ->info('The user account manager.')
                    ->isRequired()
                ->end()
                ->scalarNode('pairwise_subject_identifier')
                    ->defaultNull()
                ->end()
            ->end();
        $this->addClaimsSection($pluginNode);
        $this->addIdTokenSection($pluginNode);
        $this->addUserinfoEndpointSection($pluginNode);
        $this->addJwksUriSection($pluginNode);
        $this->addMetadataSection($pluginNode);
        $this->addIssuerDiscoverySection($pluginNode);
        $this->addSessionManagementSection($pluginNode);
    }

    private function addSessionManagementSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('session_management')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return $value['enabled'] && empty($value['path']);
                        })->thenInvalid('The option "path" must be set when the Session Management is enabled.')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return $value['enabled'] && empty($value['storage_name']);
                        })->thenInvalid('The option "storage_name" must be set when the Session Management is enabled.')
                    ->end()
                    ->validate()
                        ->ifTrue(function ($value) {
                            return $value['enabled'] && empty($value['template']);
                        })->thenInvalid('The option "template" must be set when the Session Management is enabled.')
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->info('Enable the session management.')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('storage_name')
                            ->defaultValue('oidc_browser_state')
                        ->end()
                        ->scalarNode('template')
                            ->info('The template of the OP iframe.')
                            ->defaultValue('@OAuth2FrameworkServerBundle/iframe/iframe.html.twig')
                        ->end()
                        ->scalarNode('path')
                            ->info('The route of the session iframe.')
                            ->defaultValue('/session/iframe')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addClaimsSection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('claims_locales_supported')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->info('A list of claim locales supported by this server (optional).')
                    ->treatNullLike([])
                ->end()
                ->arrayNode('claims_supported')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->info('A list of claim supported by this server (optional).')
                    ->treatNullLike([])
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addIdTokenSection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('id_token')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('manager')
                            ->info('The ID Token manager.')
                            ->defaultValue('oauth2_server.openid_connect.id_token.manager.default')
                        ->end()
                        ->scalarNode('signature_algorithm')
                            ->isRequired()
                        ->end()
                        ->scalarNode('signature_key_set')
                            ->isRequired()
                        ->end()
                        ->scalarNode('issuer')
                            ->isRequired()
                        ->end()
                        ->booleanNode('response_type')
                            ->defaultTrue()
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
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addUserinfoEndpointSection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('userinfo_endpoint')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                        ->scalarNode('path')
                            ->info('The path to the userinfo endpoint')
                            ->defaultValue('/userinfo')
                        ->end()
                        ->arrayNode('signature')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')->defaultFalse()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addJwksUriSection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('jwks_uri')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('route_name')
                            ->info('The route name to the JWKSet. Set null to disable that feature.')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('route_parameters')
                            ->info('Route parameters (optional).')
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                            ->treatNullLike([])
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addMetadataSection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('metadata')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $pluginNode
     */
    private function addIssuerDiscoverySection(ArrayNodeDefinition $pluginNode)
    {
        $pluginNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('issuer_discovery')
                    ->defaultValue([])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('path')->isRequired()->end()
                            ->scalarNode('issuer')->isRequired()->end()
                            ->scalarNode('server')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $pluginConfiguration, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $files = ['id_token_manager', 'userinfo', 'userinfo.scope_support', 'id_token_extension', 'metadata', 'issuer_discovery', 'claim_source_manager'];

        $parameters = [
            'oauth2_server.openid_connect.user_account_manager' => ['type' => 'alias', 'path' => '[user_account_manager]'],
            'oauth2_server.openid_connect.id_token.manager' => ['type' => 'alias', 'path' => '[id_token][manager]'],
            'oauth2_server.openid_connect.id_token.manager.signature_key_set' => ['type' => 'alias', 'path' => '[id_token][signature_key_set]'],
            'oauth2_server.openid_connect.id_token.manager.header_checkers' => ['type' => 'parameter', 'path' => '[id_token][header_checkers]'],
            'oauth2_server.openid_connect.id_token.manager.claim_checkers' => ['type' => 'parameter', 'path' => '[id_token][claim_checkers]'],
            'oauth2_server.openid_connect.issuer_discovery' => ['type' => 'parameter', 'path' => '[issuer_discovery]'],
            'oauth2_server.openid_connect.pairwise_subject_identifier' => ['type' => 'parameter', 'path' => '[pairwise_subject_identifier]'],
            'oauth2_server.openid_connect.id_token.manager.issuer' => ['type' => 'parameter', 'path' => '[id_token][issuer]'],
            'oauth2_server.openid_connect.id_token.manager.signature_algorithm' => ['type' => 'parameter', 'path' => '[id_token][signature_algorithm]'],
            'oauth2_server.openid_connect.userinfo_endpoint.enabled' => ['type' => 'parameter', 'path' => '[userinfo_endpoint][enabled]'],
            'oauth2_server.openid_connect.userinfo_endpoint.signature.enabled' => ['type' => 'parameter', 'path' => '[userinfo_endpoint][signature][enabled]'],
            'oauth2_server.openid_connect.metadata.enabled' => ['type' => 'parameter', 'path' => '[metadata][enabled]'],
            'oauth2_server.openid_connect.id_token.response_type.id_token' => ['type' => 'parameter', 'path' => '[response_type][id_token]'],
            'oauth2_server.openid_connect.claims_supported' => ['type' => 'parameter', 'path' => '[claims_supported]', 'callback' => function ($data) {
                return array_unique($data);
            }],
            'oauth2_server.openid_connect.claims_locales_supported' => ['type' => 'parameter', 'path' => '[claims_locales_supported]', 'callback' => function ($data) {
                return array_unique($data);
            }],
        ];

        if (true === $pluginConfiguration['id_token']['response_type']) {
            $files[] = 'id_token_response_type';
        }

        if (true === $pluginConfiguration['userinfo_endpoint']['enabled']) {
            $files[] = 'userinfo_endpoint';
            $parameters['oauth2_server.openid_connect.userinfo_endpoint.path'] = ['type' => 'parameter', 'path' => '[userinfo_endpoint][path]'];
            $parameters['oauth2_server.openid_connect.userinfo_endpoint.signature.enabled'] = ['type' => 'parameter', 'path' => '[userinfo_endpoint][signature][enabled]'];
            if (true === $pluginConfiguration['userinfo_endpoint']['signature']['enabled']) {
                $parameters['oauth2_server.openid_connect.userinfo_endpoint.signature.signature_key_set'] = ['type' => 'alias', 'path' => '[id_token][signature_key_set]'];
                $parameters['oauth2_server.openid_connect.userinfo_endpoint.signature.issuer'] = ['type' => 'parameter', 'path' => '[id_token][issuer]'];
                $parameters['oauth2_server.openid_connect.userinfo_endpoint.signature.signature_algorithm'] = ['type' => 'parameter', 'path' => '[id_token][signature_algorithm]'];
            }
        }

        if (null !== $pluginConfiguration['jwks_uri']['route_name']) {
            $parameters['oauth2_server.openid_connect.jwks_uri.route_name'] = ['type' => 'parameter', 'path' => '[jwks_uri][route_name]'];
            $parameters['oauth2_server.openid_connect.jwks_uri.route_parameters'] = ['type' => 'parameter', 'path' => '[jwks_uri][route_parameters]'];
        }

        if (true === $pluginConfiguration['metadata']['enabled']) {
            $files[] = 'metadata';
        }

        if (true === $pluginConfiguration['session_management']['enabled']) {
            $files[] = 'session_state_parameter_extension';
            $parameters['oauth2_server.openid_connect.session_state_parameter_extension.enabled'] = ['type' => 'parameter', 'path' => '[session_management][enabled]'];
            $parameters['oauth2_server.openid_connect.session_state_parameter_extension.storage_name'] = ['type' => 'parameter', 'path' => '[session_management][storage_name]'];
            $parameters['oauth2_server.openid_connect.session_state_parameter_extension.template'] = ['type' => 'parameter', 'path' => '[session_management][template]'];
            $parameters['oauth2_server.openid_connect.session_state_parameter_extension.path'] = ['type' => 'parameter', 'path' => '[session_management][path]'];
        }

        $this->loadParameters($parameters, $pluginConfiguration, $container);

        $files[] = 'extensions/id_token_hint';
        $files[] = 'extensions/max_age';
        $files[] = 'extensions/prompt_login';
        $files[] = 'extensions/prompt_none';

        foreach ($files as $basename) {
            $loader->load(sprintf('%s.yml', $basename));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PairwiseSubjectIdentifierCompilerPass());
        $container->addCompilerPass(new UserInfoScopeSupportCompilerPass());
        $container->addCompilerPass(new UserInfoEndpointSignatureSupportCompilerPass());
        $container->addCompilerPass(new JwksUriMetadataCompilerPass());
        $container->addCompilerPass(new IdTokenMetadataCompilerPass());
        $container->addCompilerPass(new IssuerDiscoveryCompilerPass());
        $container->addCompilerPass(new ClaimSourceCompilerPass());
        $container->addCompilerPass(new MetadataRouteCompilerPass());
        $container->addCompilerPass(new UserinfoRouteCompilerPass());
        $container->addCompilerPass(new SessionIFrameRouteCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $container)
    {
        $container->get('twig.loader')->addPath(__DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'views', 'OAuth2FrameworkServerBundle');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $config = current($container->getExtensionConfig('oauth2_server'));
        Assertion::keyExists($config, 'scope', 'The "ScopeManagerPlugin" must be enabled to use the OpenIdConnectPlugin.');

        $config = current($container->getExtensionConfig('oauth2_server'));
        if (array_key_exists('token_endpoint', $config)) {
            foreach (['user_account_manager'] as $name) {
                $config[$this->name()][$name] = $config['token_endpoint'][$name];
            }
        }
        if (array_key_exists('jwt_access_token', $config)) {
            $config[$this->name()]['id_token']['issuer'] = $config['jwt_access_token']['issuer'];
        }

        $this->prependJoseServices($container);

        $container->prependExtensionConfig('oauth2_server', $config);
    }

    /**
     * {@inheritdoc}
     */
    public function prependJoseServices(ContainerBuilder $container)
    {
        $bundle_config = current($container->getExtensionConfig('oauth2_server'))[$this->name()];
        $this->updateJoseBundleConfigurationForSigner($container, $this->name(), $bundle_config['id_token']);
        $this->updateJoseBundleConfigurationForVerifier($container, $this->name(), $bundle_config['id_token']);
        $this->updateJoseBundleConfigurationForChecker($container, $this->name(), $bundle_config['id_token']);
        $this->updateJoseBundleConfigurationForJWTCreator($container, $this->name());
        $this->updateJoseBundleConfigurationForJWTLoader($container, $this->name());
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
    private function updateJoseBundleConfigurationForVerifier(ContainerBuilder $container, $service_name, array $bundle_config)
    {
        ConfigurationHelper::addVerifier($container, $service_name, [$bundle_config['signature_algorithm']], false);
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
     */
    private function updateJoseBundleConfigurationForJWTCreator(ContainerBuilder $container, $service_name)
    {
        $encrypter = null;
        ConfigurationHelper::addJWTCreator($container, $service_name, sprintf('jose.signer.%s', $service_name), $encrypter, false);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string                                                  $service_name
     */
    private function updateJoseBundleConfigurationForJWTLoader(ContainerBuilder $container, $service_name)
    {
        $decrypter = null;
        ConfigurationHelper::addJWTLoader($container, $service_name, sprintf('jose.verifier.%s', $service_name), sprintf('jose.checker.%s', $service_name), $decrypter, false);
    }
}
