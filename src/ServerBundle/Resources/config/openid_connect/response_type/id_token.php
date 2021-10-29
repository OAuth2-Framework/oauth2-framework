<?php

declare(strict_types=1);

use OAuth2Framework\Component\OpenIdConnect\IdTokenBuilderFactory;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(IdTokenResponseType::class)
        ->args([
            service(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            service('jose.jws_builder.oauth2_server.openid_connect.id_token'),
            service('jose.key_set.oauth2_server.openid_connect.id_token'),
            service('jose.encrypter.oauth2_server.openid_connect.id_token')
                ->nullOnInvalid(),
        ])
    ;
};
