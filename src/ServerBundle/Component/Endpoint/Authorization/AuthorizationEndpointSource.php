<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\Extension;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationChecker;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationEndpointRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ConsentScreenExtensionCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ParameterCheckerCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseModeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseTypeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\TemplatePathCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\UserAuthenticationCheckerCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class AuthorizationEndpointSource implements Component
{
    /**
     * @var Component[]
     */
    private $subComponents = [];

    public function __construct()
    {
        $this->subComponents = [
            new ResponseModeSource(),
            new RequestObjectSource(),
        ];
    }

    public function name(): string
    {
        return 'authorization';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['authorization'];
        $container->setParameter('oauth2_server.endpoint.authorization.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(ResponseType::class)->addTag('oauth2_server_response_type');
        $container->registerForAutoconfiguration(ResponseMode::class)->addTag('oauth2_server_response_mode');
        $container->registerForAutoconfiguration(ParameterChecker::class)->addTag('oauth2_server_authorization_parameter_checker');
        $container->registerForAutoconfiguration(UserAuthenticationChecker::class)->addTag('oauth2_server_user_authentication_checker');
        $container->registerForAutoconfiguration(Extension::class)->addTag('oauth2_server_consent_screen_extension');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        $loader->load('authorization.php');

        $container->setAlias(UserAccountDiscovery::class, $config['user_discovery']);
        if (!empty($config['consent_repository'])) {
            $container->setAlias(ConsentRepository::class, $config['consent_repository']);
        }

        $container->setParameter('oauth2_server.endpoint.authorization.authorization_endpoint_path', $config['authorization_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.login_endpoint_path', $config['login_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.consent_endpoint_path', $config['consent_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.select_account_endpoint_path', $config['select_account_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.process_endpoint_path', $config['process_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.authorization.enforce_state', $config['enforce_state']);
        $container->setAlias('oauth2_server.endpoint.authorization.handler.consent', $config['consent_handler']);
        $container->setAlias('oauth2_server.endpoint.authorization.handler.login', $config['login_handler']);
        $container->setAlias('oauth2_server.endpoint.authorization.handler.select_account', $config['select_account_handler']);

        if ($container->hasAlias('oauth2_server.http_client')) {
            $loader->load('sector_identifier_uri.php');
        }
        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!\class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled();

        $childNode->children()
            ->scalarNode('authorization_endpoint_path')
            ->info('The path to the authorization endpoint.')
            ->defaultValue('/authorize')
            ->end()
            ->scalarNode('login_endpoint_path')
            ->info('The path to the login endpoint.')
            ->defaultValue('/authorize/{authorization_id}/login')
            ->end()
            ->scalarNode('consent_endpoint_path')
            ->info('The path to the consent endpoint.')
            ->defaultValue('/authorize/{authorization_id}/consent')
            ->end()
            ->scalarNode('select_account_endpoint_path')
            ->info('The path to the select account endpoint.')
            ->defaultValue('/authorize/{authorization_id}/select_account')
            ->end()
            ->scalarNode('process_endpoint_path')
            ->info('The path to the process endpoint.')
            ->defaultValue('/authorize/{authorization_id}/process')
            ->end()
            ->scalarNode('host')
            ->info('If set, the routes will be limited to that host')
            ->defaultValue('')
            ->treatFalseLike('')
            ->treatNullLike('')
            ->end()
            ->scalarNode('user_discovery')
            ->info('The user discovery service.')
            ->isRequired()
            ->end()
            ->scalarNode('consent_repository')
            ->info('The pre-configured consent repository service.')
            ->defaultNull()
            ->end()
            ->scalarNode('enforce_state')
            ->info('If true the "state" parameter is mandatory (recommended).')
            ->defaultFalse()
            ->end()
            ->scalarNode('consent_handler')
            ->info('The consent handler.')
            ->isRequired()
            ->end()
            ->scalarNode('login_handler')
            ->info('The login handler.')
            ->isRequired()
            ->end()
            ->scalarNode('select_account_handler')
            ->info('The account selection handler.')
            ->isRequired()
            ->end()
            ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (!\class_exists(AuthorizationEndpoint::class)) {
            return [];
        }
        if (!$config['endpoint']['authorization']['enabled']) {
            return [];
        }

        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = \array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container)
    {
        if (!\class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new AuthorizationEndpointRouteCompilerPass());
        $container->addCompilerPass(new AuthorizationRequestMetadataCompilerPass());
        $container->addCompilerPass(new ConsentScreenExtensionCompilerPass());
        $container->addCompilerPass(new ParameterCheckerCompilerPass());
        $container->addCompilerPass(new ResponseModeCompilerPass());
        $container->addCompilerPass(new ResponseTypeCompilerPass());
        $container->addCompilerPass(new TemplatePathCompilerPass());
        $container->addCompilerPass(new UserAuthenticationCheckerCompilerPass());

        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
