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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

final class OAuth2Factory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.oauth2_security.'.$id;
        $container->setDefinition($providerId, new DefinitionDecorator('oauth2_server.security.authentication.provider'));

        $listenerId = 'security.authentication.listener.oauth2_security.'.$id;
        $listenerDefinition = $container->setDefinition($listenerId, new DefinitionDecorator('oauth2_server.security.authentication.listener'));
        $listenerDefinition->replaceArgument(3, new Reference($config['access_token_handler']));

        return [$providerId, $listenerId, 'oauth2_server.security.entry_point'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition(): string
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey(): string
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('access_token_handler_manager')
                    ->info('The access token handler manager has to retrieve access tokens on demand. Access token can be find from a database, the introspection or any other method.')
                    ->isRequired()
                ->end()
            ->end();
    }
}
