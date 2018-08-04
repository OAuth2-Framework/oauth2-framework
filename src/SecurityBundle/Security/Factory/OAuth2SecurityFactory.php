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

namespace OAuth2Framework\SecurityBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OAuth2SecurityFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.oauth2.'.$id;
        $container
            ->setDefinition($providerId, new ChildDefinition('oauth2_security.provider'))
            ->setAutowired(true)
        ;

        $listenerId = 'security.authentication.listener.oauth2.'.$id;
        $container
            ->setDefinition($listenerId, new ChildDefinition('oauth2_security.listener'))
            ->setArguments([
                new Reference(TokenStorageInterface::class),
                new Reference('security.authentication.manager'),
                new Reference('oauth2_security.token_type_manager'),
                new Reference('oauth2_security.access_token_handler_manager'),
            ])
        ;

        return [$providerId, $listenerId, 'oauth2_security.entry_point'];
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
    }
}
