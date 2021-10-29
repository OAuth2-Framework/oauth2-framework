<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientAuthentication\ClientSecretBasic;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientSecretBasic::class)
        ->args([
            '%oauth2_server.client_authentication.client_secret_basic.realm%',
            '%oauth2_server.client_authentication.client_secret_basic.secret_lifetime%',
        ])
    ;
};
