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
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\UserAccount\UserAccountDiscovery;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationEndpointRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\AuthorizationRequestMetadataCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ConsentScreenExtensionCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ParameterCheckerCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseModeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\ResponseTypeCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\TemplatePathCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\RequestObjectCompilerPass;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentScreen\Extension;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode\ResponseMode;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler\UserAccountCheckerCompilerPass;
use OAuth2Framework\ServerBundle\Form\Type\AuthorizationType;
use OAuth2Framework\ServerBundle\Service\SymfonyUserDiscovery;
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

    /**
     * AuthorizationEndpointSource constructor.
     */
    public function __construct()
    {
        $this->subComponents = [
            new ResponseModeSource(),
            new RequestObjectSource(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'authorization';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
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
        $container->registerForAutoconfiguration(UserAccountChecker::class)->addTag('oauth2_server_user_account_checker');
        $container->registerForAutoconfiguration(Extension::class)->addTag('oauth2_server_consent_screen_extension');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        $loader->load('authorization.php');
        $loader->load('user_account_discovery.php');

        $container->setAlias(UserAccountDiscovery::class, $config['user_account_discovery']);

        $container->setParameter('oauth2_server.endpoint.authorization.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.authorization.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.authorization.login_route_name', $config['login_route_name']);
        $container->setParameter('oauth2_server.endpoint.authorization.login_route_parameters', $config['login_route_parameters']);
        $container->setParameter('oauth2_server.endpoint.authorization.template', $config['template']);
        $container->setParameter('oauth2_server.endpoint.authorization.enforce_state', $config['enforce_state']);
        $container->setParameter('oauth2_server.endpoint.authorization.form', $config['form']);
        $container->setParameter('oauth2_server.endpoint.authorization.type', $config['type']);

        foreach ($this->subComponents as $subComponent) {
            $subComponent->load($configs, $container);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled();

        $childNode->children()
            ->scalarNode('path')
                ->info('The path to the authorization endpoint.')
                ->defaultValue('/authorize')
            ->end()
            ->scalarNode('host')
            ->info('If set, the route will be limited to that host')
                ->defaultValue('')
                ->treatFalseLike('')
                ->treatNullLike('')
            ->end()
            ->scalarNode('login_route_name')
                ->info('The name of the login route. Will be converted into URL and used to redirect the user if not logged in. If you use "FOSUserBundle", the route name should be "fos_user_security_login".')
            ->end()
            ->arrayNode('login_route_parameters')
                ->info('Parameters associated to the login route (optional).')
                ->useAttributeAsKey('name')
                ->scalarPrototype()->end()
                ->treatNullLike([])
            ->end()
            ->scalarNode('user_account_discovery')
                ->info('The user account discovery service.')
                ->defaultValue(SymfonyUserDiscovery::class)
            ->end()
            ->scalarNode('template')
                ->info('The consent page template.')
                ->defaultValue('@OAuth2FrameworkServerBundle/authorization/authorization.html.twig')
            ->end()
            ->scalarNode('enforce_state')
                ->info('If true the "state" parameter is mandatory (recommended).')
                ->defaultFalse()
            ->end()
            ->scalarNode('form')
                ->info('If form used for authorization requests.')
                ->defaultValue('oauth2_server_authorization_form')
            ->end()
            ->scalarNode('type')
                ->info('Form type.')
                ->defaultValue(AuthorizationType::class)
            ->end()
        ->end();

        foreach ($this->subComponents as $subComponent) {
            $subComponent->getNodeDefinition($childNode, $node);
        }
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        if (!class_exists(AuthorizationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new AuthorizationEndpointRouteCompilerPass());
        $container->addCompilerPass(new RequestObjectCompilerPass());
        $container->addCompilerPass(new AuthorizationRequestMetadataCompilerPass());
        $container->addCompilerPass(new ConsentScreenExtensionCompilerPass());
        $container->addCompilerPass(new ParameterCheckerCompilerPass());
        $container->addCompilerPass(new ResponseModeCompilerPass());
        $container->addCompilerPass(new ResponseTypeCompilerPass());
        $container->addCompilerPass(new TemplatePathCompilerPass());
        $container->addCompilerPass(new UserAccountCheckerCompilerPass());

        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
