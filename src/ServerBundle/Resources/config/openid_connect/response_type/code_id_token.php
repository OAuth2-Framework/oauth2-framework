<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\CodeIdTokenResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\IdTokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(CodeIdTokenResponseType::class)
        ->args([service(AuthorizationCodeResponseType::class), service(IdTokenResponseType::class)])
    ;
};
