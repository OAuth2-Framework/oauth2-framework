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

namespace OAuth2Framework\SecurityBundle\Security\Factory;

use OAuth2Framework\SecurityBundle\Security\Authentication\Provider\OAuth2Provider;
use OAuth2Framework\SecurityBundle\Security\EntryPoint\OAuth2EntryPoint;
use OAuth2Framework\SecurityBundle\Security\Handler\DefaultFailureHandler;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class OAuth2SecurityFactory implements SecurityFactoryInterface
{
    /**
     * @param string      $id
     * @param array       $config
     * @param string      $userProviderId
     * @param null|string $defaultEntryPointId
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId): array
    {
        $authProviderId = $this->createAuthProvider($container, $id);
        $entryPointId = $this->createEntryPoint($container, $id, $config);
        $listenerId = $this->createListener($container, $id, $config);

        return [$authProviderId, $listenerId, $entryPointId];
    }

    public function getPosition(): string
    {
        return 'pre_auth';
    }

    public function getKey(): string
    {
        return 'oauth2';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node): void
    {
        // @var ArrayNodeDefinition $node
        $node
            ->children()
            ->scalarNode('user_provider')->defaultNull()->end()
            ->scalarNode('failure_handler')->defaultValue(DefaultFailureHandler::class)->end()
            ->scalarNode('http_message_factory')->defaultValue('sensio_framework_extra.psr7.http_message_factory')->end()
            ->end()
        ;
    }

    private function createAuthProvider(ContainerBuilder $container, string $id): string
    {
        $providerId = 'security.authentication.provider.oauth2.'.$id;
        $container->setDefinition($providerId, new ChildDefinition(OAuth2Provider::class));

        return $providerId;
    }

    private function createListener(ContainerBuilder $container, string $id, array $config): string
    {
        $listenerId = 'oauth2_security.listener';
        $listener = new ChildDefinition($listenerId);
        /*
         * ->setArguments([
         * new Reference(TokenStorageInterface::class),
         * new Reference('security.authentication.manager'),
         * new Reference('oauth2_security.token_type_manager'),
         * new Reference('oauth2_security.access_token_handler_manager'),
         * ])
         */
        $listener->replaceArgument(0, new Reference($config['http_message_factory']));
        //$listener->replaceArgument(13, $id);
        //$listener->replaceArgument(14, $config);
        //$listener->replaceArgument(16, new Reference($config['failure_handler']));
        $listenerId .= '.'.$id;
        $container->setDefinition($listenerId, $listener);

        return $listenerId;
    }

    private function createEntryPoint(ContainerBuilder $container, string $id, array $config): string
    {
        $entryPointId = 'oauth2.security.authentication.entrypoint.'.$id;
        $entryPoint = new ChildDefinition(OAuth2EntryPoint::class);
        $entryPoint->replaceArgument(0, new Reference($config['failure_handler']));
        $container->setDefinition($entryPointId, $entryPoint);

        return $entryPointId;
    }
}
