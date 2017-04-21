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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointSource extends ActionableSource
{
    /**
     * AuthorizationEndpointSource constructor.
     */
    public function __construct()
    {
        $this->addSubSource(new AuthorizationEndpointRequestObjectSource());
        $this->addSubSource(new AuthorizationEndpointResponseModeSource());
        $this->addSubSource(new AuthorizationEndpointPreConfiguredAuthorizationSource());
    }

    /**
     * {@inheritdoc}
     */
    protected function continueLoading(string $path, ContainerBuilder $container, array $config)
    {
        foreach ($config as $k => $v) {
            $container->setParameter($path.'.'.$k, $v);
        }

        $loader = new PhpConfigFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint'));
        $loader->load('authorization.php');
    }

    /**
     * {@inheritdoc}
     */
    protected function name(): string
    {
        return 'authorization';
    }

    /**
     * {@inheritdoc}
     */
    protected function continueConfiguration(NodeDefinition $node)
    {
        parent::continueConfiguration($node);
        $node
            ->children()
                ->scalarNode('path')
                    ->info('The path to the authorization endpoint.')
                    ->defaultValue('/authorize')
                ->end()
                ->scalarNode('login_route_name')
                    ->info('The name of the login route. Will be converted into URL and used to redirect the user if not logged in. If you use "FOSUserBundle", the route name should be "fos_user_security_login".')
                ->end()
                ->arrayNode('login_route_parameters')
                    ->info('Parameters associated to the login route (if needed).')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                    ->treatNullLike([])
                ->end()
                ->scalarNode('template')
                    ->info('The consent page template.')
                    ->defaultValue('@OAuth2FrameworkServerBundle/authorization/authorization.html.twig')
                ->end()
                ->scalarNode('allow_token_type_parameter')
                    ->info('If true the "token_type" parameter is allowed, else it will be ignored.')
                    ->defaultFalse()
                ->end()
                ->scalarNode('enforce_state')
                    ->info('If true the "state" parameter is mandatory (highly recommended).')
                    ->defaultFalse()
                ->end()
                ->scalarNode('enforce_secured_redirect_uri')
                    ->info('If true only secured redirect URIs are allowed.')
                    ->defaultTrue()
                ->end()
                ->scalarNode('enforce_redirect_uri_storage')
                    ->info('If true redirect URIs must be registered by the client to be used.')
                    ->defaultTrue()
                ->end()
            ->end();
    }
}
