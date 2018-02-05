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

namespace OAuth2Framework\Bundle\DependencyInjection\Compiler;

use OAuth2Framework\Bundle\TokenEndpointAuthMethod\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ClientAssertionJWTEncryptionSupportConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ClientAssertionJwt::class) || !$container->getParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.enabled')) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $is_encryption_required = $container->getParameter('oauth2_server.client_authentication.client_assertion_jwt.encryption.required');

        $definition->addMethodCall('enableEncryptedAssertions', [new Reference('jose.jwe_loader.client_assertion_jwt'), $is_encryption_required, new Reference('jose.key_set.oauth2_server.key_set.encryption')]);
    }
}
