<?php

declare(strict_types=1);

use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenTokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(IdTokenTokenResponseType::class)
        ->args([service(IdTokenResponseType::class), service(TokenResponseType::class)])
    ;
};
