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

class AuthorizationFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.authorization_factory')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.authorization_factory');
        $parameter = $container->getParameter('oauth2_server.authorization_endpoint.option.allow_response_mode_parameter');

        if (true === $parameter) {
            $definition->addMethodCall('enableResponseModeParameterSupport');
        } else {
            $definition->addMethodCall('disableResponseModeParameterSupport');
        }
    }
}
