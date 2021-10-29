<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientRegistrationEndpoint\Rule\SoftwareRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(SoftwareRule::class)
        ->args([
            service('jose.jws_loader.oauth2_server.endpoint.client_registration.software_statement'),
            service('jose.key_set.oauth2_server.endpoint.client_registration.software_statement'),
            '%oauth2_server.endpoint.client_registration.software_statement.required%',
            '%oauth2_server.endpoint.client_registration.software_statement.allowed_signature_algorithms%',
        ])
    ;
};
