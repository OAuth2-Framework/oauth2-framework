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

namespace OAuth2Framework\Bundle\Server\ClientCredentialsGrantTypePlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClientCredentialsConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.client_credentials_grant_type')) {
            return;
        }

        $definition = $container->getDefinition('oauth2_server.client_credentials_grant_type');

        if (true === $container->getParameter('oauth2_server.client_credentials_grant_type.issue_refresh_token_with_client_credentials_grant_type')) {
            $definition->addMethodCall('enableRefreshTokenIssuanceWithAccessToken');
        } else {
            $definition->addMethodCall('disableRefreshTokenIssuanceWithAccessToken');
        }
    }
}
