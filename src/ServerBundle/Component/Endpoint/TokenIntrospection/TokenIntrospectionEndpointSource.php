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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection;

use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler\TokenIntrospectionRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenIntrospection\Compiler\TokenTypeHintCompilerPass;
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
        $config = $configs['endpoint']['token_introspection'];
        $container->setParameter('oauth2_server.endpoint.token_introspection.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->registerForAutoconfiguration(TokenTypeHint::class)->addTag('oauth2_server_introspection_type_hint');
        $container->setParameter('oauth2_server.endpoint.token_introspection.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.token_introspection.host', $config['host']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/token_introspection'));
        $loader->load('introspection.php');
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        $rootNode->validate()
            ->ifTrue(function ($config) {
                return null === $config['resource_server']['repository'];
            })
            ->thenInvalid('The resource server repository must be set when the introspection endpoint is enabled')
        ->end();

        $node->children()
            ->arrayNode($this->name())
                ->canBeEnabled()
                ->children()
                    ->scalarNode('path')
                        ->info('The token introspection endpoint path')
                        ->defaultValue('/token/introspection')
                    ->end()
                    ->scalarNode('host')
                        ->info('If set, the route will be limited to that host')
                        ->defaultValue('')
                        ->treatNullLike('')
                        ->treatFalseLike('')
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
