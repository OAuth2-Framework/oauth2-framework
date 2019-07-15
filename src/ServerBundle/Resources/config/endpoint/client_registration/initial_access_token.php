<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(InitialAccessTokenMiddleware::class)
        ->args([
            ref('client_registration_bearer_token'),
            ref(InitialAccessTokenRepository::class),
            '%oauth2_server.endpoint.client_registration.initial_access_token.required%',
        ])
    ;

    $container->set('client_registration_bearer_token_finder')
        ->class(AuthorizationHeaderTokenFinder::class)
    ;

    $container->set('client_registration_bearer_token')
        ->class(BearerToken::class)
        ->args([
            '%oauth2_server.endpoint.client_registration.initial_access_token.realm%',
        ])
        ->call('addTokenFinder', [ref('client_registration_bearer_token_finder')])
        ;
};
