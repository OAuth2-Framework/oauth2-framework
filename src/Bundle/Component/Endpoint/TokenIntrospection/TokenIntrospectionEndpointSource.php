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

namespace OAuth2Framework\Bundle\Component\Endpoint\TokenIntrospection;

use OAuth2Framework\Bundle\Component\Component;
use OAuth2Framework\Bundle\Component\Endpoint\TokenIntrospection\Compiler\ResourceServerAuthenticationMethodCompilerPass;
use OAuth2Framework\Bundle\Component\Endpoint\TokenIntrospection\Compiler\TokenIntrospectionRouteCompilerPass;
use OAuth2Framework\Bundle\Component\Endpoint\TokenIntrospection\Compiler\TokenTypeHintCompilerPass;
use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\TokenIntrospectionEndpoint\TokenTypeHint;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenIntrospectionEndpointSource implements Component
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'token_introspection';
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if (!$configs['endpoint']['token_introspection']['enabled']) {
            return;
        }
        $container->registerForAutoconfiguration(AuthenticationMethodManager::class)->addTag('resource_server_authentication_method');
        $container->registerForAutoconfiguration(TokenTypeHint::class)->addTag('oauth2_server_introspection_type_hint');
        $container->setParameter('oauth2_server.endpoint.token_introspection.path', $configs['endpoint']['token_introspection']['path']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/token_introspection'));
        $loader->load('introspection.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node)
    {
        $node->children()
            ->arrayNode($this->name())
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('path')
                        ->info('The token introspection endpoint path')
                        ->defaultValue('/token/introspection')
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TokenTypeHintCompilerPass());
        $container->addCompilerPass(new ResourceServerAuthenticationMethodCompilerPass());
        $container->addCompilerPass(new TokenIntrospectionRouteCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
