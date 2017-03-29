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

namespace OAuth2Framework\Bundle\Server\JWTBearerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class JWTBearerConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.jwt_bearer_grant_type')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.jwt_bearer_grant_type');
        $issue_refresh_token = $container->getParameter('oauth2_server.jwt_bearer_grant_type.issue_refresh_token');

        $definition->addMethodCall(sprintf('%sRefreshTokenIssuanceWithAccessToken', $issue_refresh_token ? 'enable' : 'disable'));

        if (true === $container->getParameter('oauth2_server.jwt_bearer_grant_type.encryption.enabled')) {
            $definition->addMethodCall('enableEncryptedAssertions', [
                $container->getParameter('oauth2_server.jwt_bearer_grant_type.encryption.required'),
                new Reference($container->getAlias('oauth2_server.jwt_bearer_grant_type.encryption.key_set')),
            ]);
        }
    }
}
