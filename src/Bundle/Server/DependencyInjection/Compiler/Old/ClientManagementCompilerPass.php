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

namespace OAuth2Framework\Bundle\Server\ClientManagerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientManagementCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oauth2_server.client_registration_endpoint') || !$container->hasDefinition('oauth2_server.client_configuration_endpoint')) {
            return;
        }

        $client_registration_endpoint = $container->getDefinition('oauth2_server.client_registration_endpoint');
        $client_configuration_endpoint = $container->getDefinition('oauth2_server.client_configuration_endpoint');

        if (true === $container->getParameter('oauth2_server.initial_access_token.enabled')) {
            $client_registration_endpoint->addMethodCall('enableInitialAccessTokenSupport', [
                new Reference($container->getAlias('oauth2_server.initial_access_token.manager')),
            ]);

            $is_initial_access_token_required = $container->getParameter('oauth2_server.initial_access_token.required');
            $method = sprintf('%sallowRegistrationWithoutInitialAccessToken', $is_initial_access_token_required ? 'dis' : '');
            $client_registration_endpoint->addMethodCall($method, []);
        }
        if (true === $container->getParameter('oauth2_server.software_statement.enabled')) {
            $client_registration_endpoint->addMethodCall('enableSoftwareStatementSupport', [
                new Reference('jose.jwt_loader.oauth2_server_software_statement'),
                new Reference($container->getAlias('oauth2_server.software_statement.key_set')),
            ]);
            $client_configuration_endpoint->addMethodCall('enableSoftwareStatementSupport', [
                new Reference('jose.jwt_loader.oauth2_server_software_statement'),
                new Reference($container->getAlias('oauth2_server.software_statement.key_set')),
            ]);

            $is_software_statement_required = $container->getParameter('oauth2_server.software_statement.required');
            $method = sprintf('%sallowRegistrationWithoutSoftwareStatement', $is_software_statement_required ? 'dis' : '');
            $client_registration_endpoint->addMethodCall($method, []);
            $client_configuration_endpoint->addMethodCall($method, []);
        }
    }
}
