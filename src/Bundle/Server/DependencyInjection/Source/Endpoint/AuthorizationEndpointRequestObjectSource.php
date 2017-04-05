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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Source\Endpoint;

use Fluent\PhpConfigFileLoader;
use OAuth2Framework\Bundle\Server\DependencyInjection\Source\ActionableSource;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthorizationEndpointRequestObjectSource extends ActionableSource
{
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
        return 'request_object';
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
                    ->cannotBeEmpty()
                    ->defaultValue('@OAuth2FrameworkServerBundle/authorization/authorization.html.twig')
                ->end()
            ->end();
    }
}
/*
path:
            #allow_scope_selection: true
            :
            request_object:
                enabled: true
                signature_algorithms: ['RS512', 'HS512']
                claim_checkers: ['exp', 'iat', 'nbf', 'authorization_endpoint_aud']
                header_checkers: ['crit']
                encryption:
                    enabled: true
                    required: true
                    key_set: 'jose.key_set.encryption'
                    key_encryption_algorithms: ['RSA-OAEP-256']
                    content_encryption_algorithms: ['A256CBC-HS512']
                reference:
                    enabled: true
                    uris_registration_required: true
            pre_configured_authorization:
                enabled: true
            enforce_secured_redirect_uri:
                enabled: true
            enforce_redirect_uri_storage:
                enabled: true
            enforce_state:
                enabled: true
            allow_response_mode_parameter:
                enabled: true
*/
