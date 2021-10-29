<?php

declare(strict_types=1);

use OAuth2Framework\Component\OpenIdConnect\IdTokenLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(IdTokenLoader::class)
        ->args([
            service('jose.jws_loader.oauth2_server.openid_connect.id_token.signature'),
            service('jose.key_set.oauth2_server.openid_connect.id_token'),
            '%oauth2_server.openid_connect.id_token.signature_algorithms%',
        ])
    ;
};
