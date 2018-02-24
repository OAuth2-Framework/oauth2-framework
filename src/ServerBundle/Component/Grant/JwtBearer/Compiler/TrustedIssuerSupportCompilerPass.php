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

namespace OAuth2Framework\ServerBundle\Component\Grant\JwtBearer\Compiler;

use OAuth2Framework\Component\JwtBearerGrant\JwtBearerGrantType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TrustedIssuerSupportCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(JwtBearerGrantType::class) || !$container->hasAlias('oauth2_server.trusted_issuer_repository')) {
            return;
        }

        $definition = $container->getDefinition(JwtBearerGrantType::class);
        $definition->addMethodCall('enableTrustedIssuerSupport', [new Reference('oauth2_server.trusted_issuer_repository')]);
    }
}
