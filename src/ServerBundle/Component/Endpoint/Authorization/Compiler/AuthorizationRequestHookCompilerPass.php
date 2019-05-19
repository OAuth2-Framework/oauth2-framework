<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Component\Endpoint\Authorization\Compiler;

use OAuth2Framework\ServerBundle\Controller\AuthorizationEndpointController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthorizationRequestHookCompilerPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const TAG_NAME = 'oauth2_server_authorization_endpoint_hook';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AuthorizationEndpointController::class)) {
            return;
        }

        $definition = $container->getDefinition(AuthorizationEndpointController::class);

        $taggedServices = $this->findAndSortTaggedServices(self::TAG_NAME, $container);
        dump($taggedServices);
        /*foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('add', [new Reference($id)]);
        }*/
    }
}
