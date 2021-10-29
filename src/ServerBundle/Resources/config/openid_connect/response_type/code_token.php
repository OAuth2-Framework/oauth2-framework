<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeResponseType;
use OAuth2Framework\Component\ImplicitGrant\TokenResponseType;
use OAuth2Framework\Component\OpenIdConnect\IdTokenGrant\CodeTokenResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(CodeTokenResponseType::class)
        ->args([service(AuthorizationCodeResponseType::class), service(TokenResponseType::class)])
    ;
};
