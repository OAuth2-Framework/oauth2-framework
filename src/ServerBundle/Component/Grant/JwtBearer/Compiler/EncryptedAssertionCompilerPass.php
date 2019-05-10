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

namespace OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler;

use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EncryptedAssertionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(JwtBearerGrantType::class) || true !== $container->getParameter('oauth2_server.grant.jwt_bearer.encryption.enabled')) {
            return;
        }

        $definition = $container->getDefinition(JwtBearerGrantType::class);
        $definition->addMethodCall('enableEncryptedAssertions', [
            new Reference('jose.jwe_decrypter.oauth2_server.grant.jwt_bearer'),
            new Reference('jose.key_set.oauth2_server.grant.jwt_bearer'),
            '%oauth2_server.grant.jwt_bearer.encryption.required%',
        ]);
    }
}
