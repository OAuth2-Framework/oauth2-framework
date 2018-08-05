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

namespace OAuth2Framework\ServerBundle\Component\ClientAuthentication\Compiler;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAssertionJkuSupportCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ClientAssertionJwt::class) || !$container->getParameter('oauth2_server.client_authentication.client_assertion_jwt.jku_support') || !$container->hasDefinition(JKUFactory::class)) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $definition->addMethodCall('enableJkuSupport', [new Reference(JKUFactory::class)]);
    }
}
