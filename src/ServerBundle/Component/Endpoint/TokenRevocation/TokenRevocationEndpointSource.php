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

namespace OAuth2Framework\ServerBundle\Component\Endpoint\TokenRevocation;

use OAuth2Framework\Component\TokenRevocationEndpoint\TokenRevocationEndpoint;
use OAuth2Framework\Component\TokenRevocationEndpoint\TokenTypeHint;
use OAuth2Framework\ServerBundle\Component\Component;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenRevocation\Compiler\TokenRevocationRouteCompilerPass;
use OAuth2Framework\ServerBundle\Component\Endpoint\TokenRevocation\Compiler\TokenTypeHintCompilerPass;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class TokenRevocationEndpointSource implements Component
{
    public function name(): string
    {
        return 'token_revocation';
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        if (!\class_exists(TokenRevocationEndpoint::class)) {
            return;
        }
        $config = $configs['endpoint']['token_revocation'];
        $container->setParameter('oauth2_server.endpoint.token_revocation.enabled', $config['enabled']);
        if (!$config['enabled']) {
            return;
        }
        $container->registerForAutoconfiguration(TokenTypeHint::class)->addTag('oauth2_server_revocation_type_hint');
        $container->setParameter('oauth2_server.endpoint.token_revocation.path', $config['path']);
        $container->setParameter('oauth2_server.endpoint.token_revocation.host', $config['host']);
        $container->setParameter('oauth2_server.endpoint.token_revocation.allow_callback', $config['allow_callback']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resources/config/endpoint/token_revocation'));
        $loader->load('revocation.php');
    }

    public function getNodeDefinition(ArrayNodeDefinition $node, ArrayNodeDefinition $rootNode)
    {
        if (!\class_exists(TokenRevocationEndpoint::class)) {
            return;
        }
        $node->children()
            ->arrayNode($this->name())
            ->canBeEnabled()
            ->children()
            ->scalarNode('path')
            ->info('The token revocation endpoint path')
            ->defaultValue('/token/revocation')
            ->end()
            ->scalarNode('host')
            ->info('If set, the route will be limited to that host')
            ->defaultValue('')
            ->treatNullLike('')
            ->treatFalseLike('')
            ->end()
            ->booleanNode('allow_callback')
            ->info('If true, GET request with "callback" parameter are allowed.')
            ->defaultFalse()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container)
    {
        if (!\class_exists(TokenRevocationEndpoint::class)) {
            return;
        }
        $container->addCompilerPass(new TokenTypeHintCompilerPass());
        $container->addCompilerPass(new TokenRevocationRouteCompilerPass());
    }

    public function prepend(ContainerBuilder $container, array $config): array
    {
        return [];
    }
}
