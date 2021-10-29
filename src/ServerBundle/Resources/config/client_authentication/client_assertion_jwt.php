<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientAuthentication\ClientAssertionJwt;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientAssertionJwt::class)
        ->args([
            service('jose.jws_verifier.client_authentication.client_assertion_jwt'),
            service('jose.header_checker.client_authentication.client_assertion_jwt'),
            service('jose.claim_checker.client_authentication.client_assertion_jwt'),
            '%oauth2_server.client_authentication.client_assertion_jwt.secret_lifetime%',
        ])
    ;
};
