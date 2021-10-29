<?php

declare(strict_types=1);

use OAuth2Framework\Component\BearerTokenType\AuthorizationHeaderTokenFinder;
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenMiddleware;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(InitialAccessTokenMiddleware::class)
        ->args([
            service('client_registration_bearer_token'),
            service(InitialAccessTokenRepository::class),
            '%oauth2_server.endpoint.client_registration.initial_access_token.required%',
        ])
    ;

    $container->set('client_registration_bearer_token_finder')
        ->class(AuthorizationHeaderTokenFinder::class)
    ;

    $container->set('client_registration_bearer_token')
        ->class(BearerToken::class)
        ->args(['%oauth2_server.endpoint.client_registration.initial_access_token.realm%'])
        ->call('addTokenFinder', [service('client_registration_bearer_token_finder')])
        ;
};
