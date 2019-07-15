<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationEndpoint;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequestStorage;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\Extension;
use OAuth2Framework\Component\AuthorizationEndpoint\Hook\AuthorizationEndpointHook;
use OAuth2Framework\Component\AuthorizationEndpoint\LoginHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAuthenticationChecker;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationEndpointRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestEntryEndpointRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestHookCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ConsentScreenExtensionCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ParameterCheckerCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseModeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseTypeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\TemplatePathCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\UserAuthenticationCheckerCompilerPass;
use OAuth2Framework\ServerBundle\Service\AuthorizationRequestSessionStorage;
use OAuth2Framework\ServerBundle\Service\IgnoreAccountSelectionHandler;
use OAuth2Framework\ServerBundle\Service\RedirectAuthorizationRequestHandler;
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

    public function load(array $configs, ContainerBuilder $container): void
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
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
        $container->registerForAutoconfiguration(AuthorizationEndpointHook::class)->addTag(AuthorizationRequestHookCompilerPass::TAG_NAME);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        $loader->load('authorization.php');

        $container->setAlias(AuthorizationRequestStorage::class, $config['authorization_request_storage']);
        $container->setAlias(UserAccountDiscovery::class, $config['user_discovery']);
        $container->setAlias(AuthorizationRequestHandler::class, $config['request_handler']);
        $container->setAlias(LoginHandler::class, $config['login_handler']);
        $container->setAlias(ConsentHandler::class, $config['consent_handler']);
        $container->setAlias(SelectAccountHandler::class, $config['select_account_handler']);
        if (null !== ($config['consent_repository'])) {
            $container->setAlias(ConsentRepository::class, $config['consent_repository']);
        }

        $container->setParameter('oauth2_server.endpoint.authorization.authorization_request_entry_endpoint_path', $config['authorization_request_entry_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.authorization_endpoint_path', $config['authorization_endpoint_path']);
        $container->setParameter('oauth2_server.endpoint.authorization.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.authorization.enforce_state', $config['enforce_state']);

        if ($container->hasAlias('oauth2_server.http_client')) {
            $loader->load('sector_identifier_uri.php');
        }
        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode): void
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
        ;

        $childNode
            ->children()
            ->scalarNode('authorization_request_entry_endpoint_path')
            ->info('The path to the authorization request entry endpoint.')
            ->defaultValue('/authorize')
            ->end()
            ->scalarNode('authorization_endpoint_path')
            ->info('The path to the authorization endpoint processor.')
            ->defaultValue('/authorize/{authorization_request_id}')
            ->end()
            ->scalarNode('authorization_request_storage')
            ->info('The authorization request storage.')
            ->defaultValue(AuthorizationRequestSessionStorage::class)
            ->end()
            ->scalarNode('request_handler')
            ->info('Service that handles the auhtorization request.')
            ->defaultValue(RedirectAuthorizationRequestHandler::class)
            ->end()
            ->scalarNode('login_handler')
            ->info('Service that handles user authentication during the authorization process.')
            ->isRequired()
            ->end()
            ->scalarNode('consent_handler')
            ->info('Service that handles user consent during the authorization process.')
            ->isRequired()
            ->end()
            ->scalarNode('select_account_handler')
            ->info('Service that handles user account selection during the authorization process.')
            ->defaultValue(IgnoreAccountSelectionHandler::class)
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
            ->end()
        ;

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
            return [];
        }
        if (!$config['endpoint']['authorization']['enabled']) {
            return [];
        }

        $updatedConfig = [];
        foreach ($this->subComponents as $subComponent) {
            $updatedConfig = array_merge(
                $updatedConfig,
                $subComponent->prepend($container, $config)
            );
        }

        return $updatedConfig;
    }

    public function build(ContainerBuilder $container): void
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new AuthorizationRequestHookCompilerPass());
        $container->addCompilerPass(new AuthorizationRequestEntryEndpointRouteCompilerPass());
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
