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

use OAuth2Framework\Bundle\Server\TokenEndpointAuthMethod\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientAssertionJWTEncryptionSupportConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ClientAssertionJwt::class) || !$container->getParameter('oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.enabled')) {
            return;
        }

        $definition = $container->getDefinition(ClientAssertionJwt::class);
        $is_encryption_required = $container->getParameter('oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.required');
        $encryption_jwk_set = $container->getAlias('oauth2_server.token_endpoint_auth_method.client_assertion_jwt.encryption.key_set');

        $definition->addMethodCall('enableEncryptedAssertions', [$is_encryption_required, new Reference($encryption_jwk_set)]);
    }
}
