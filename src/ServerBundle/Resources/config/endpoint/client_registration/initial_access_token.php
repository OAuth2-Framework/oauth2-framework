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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Command;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(InitialAccessTokenMiddleware::class)
        ->args([
            ref('client_registration_bearer_token'),
            ref('oauth2_server.endpoint.client_registration.initial_access_token.repository'),
        ]);

    $container->set('client_registration_bearer_token')
        ->class(BearerToken::class)
        ->args([
            '%oauth2_server.endpoint.client_registration.initial_access_token.realm%',
            true,
            false,
            false,
        ]);

    $container->set(Command\CreateInitialAccessTokenCommandHandler::class)
        ->args([
            ref('oauth2_server.endpoint.client_registration.initial_access_token.repository'),
        ])
        ->tag('command_handler', ['handles' => Command\CreateInitialAccessTokenCommand::class]);

    $container->set(Command\RevokeInitialAccessTokenCommandHandler::class)
        ->args([
            ref('oauth2_server.endpoint.client_registration.initial_access_token.repository'),
        ])
        ->tag('command_handler', ['handles' => Command\RevokeInitialAccessTokenCommand::class]);
};
