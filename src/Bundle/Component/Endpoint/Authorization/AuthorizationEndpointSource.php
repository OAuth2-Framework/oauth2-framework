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

namespace OAuth2Framework\Bundle\Component\Endpoint\Authorization;

use OAuth2Framework\Bundle\Component\Component;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
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
        if (!$configs['endpoint']['authorization']['enabled']) {
            return;
        }

        $container->registerForAutoconfiguration(ResponseType::class)->addTag('oauth2_server_response_type');

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/authorization'));
        //$loader->load('authorization.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $childNode = $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->addDefaultsIfNotSet();

        $childNode->children()
            ->scalarNode('path')
                ->info('The path to the authorization endpoint.')
                ->defaultValue('/authorize')
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
            ->scalarNode('template')
                ->info('The consent page template.')
                ->defaultValue('@OAuth2FrameworkBundle/authorization/authorization.html.twig')
            ->end()
            ->scalarNode('enforce_state')
                ->info('If true the "state" parameter is mandatory (recommended).')
                ->defaultFalse()
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
        foreach ($this->subComponents as $component) {
            $component->build($container);
        }
    }
}
