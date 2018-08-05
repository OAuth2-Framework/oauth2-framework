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

namespace OAuth2Framework\ServerBundle\Component\OpenIdConnect\Compiler;

use OAuth2Framework\Component\OpenIdConnect\OpenIdConnectExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OpenIdConnectExtensionEncryptionCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(OpenIdConnectExtension::class) || !$container->hasDefinition('jose.jwe_builder.oauth2_server.openid_connect.id_token')) {
            return;
        }

        $definition = $container->getDefinition(OpenIdConnectExtension::class);
        $definition->addMethodCall('enableEncryption', [new Reference('jose.jwe_builder.oauth2_server.openid_connect.id_token')]);
    }
}
