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

namespace OAuth2Framework\Bundle\Server\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationFactoryMetadataCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('oauth2_server.authorization_factory')) {
            return;
        }

        $this->callFactory($container);

        if ($container->hasDefinition('oauth2_server.openid_connect.metadata')) {
            $this->callMetadata($container);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function callMetadata(ContainerBuilder $container)
    {
        $metadata = $container->getDefinition('oauth2_server.openid_connect.metadata');

        $metadata->addMethodCall('setAuthorizationFactory', [new Reference('oauth2_server.authorization_factory')]);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function callFactory(ContainerBuilder $container)
    {
        $factory = $container->getDefinition('oauth2_server.authorization_factory');

        $taggedServices = $container->findTaggedServiceIds('oauth2_server.authorization_parameter_checker');
        foreach ($taggedServices as $id => $attributes) {
            $factory->addMethodCall('addParameterChecker', [new Reference($id)]);
        }
    }
}
