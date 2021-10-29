<?php

declare(strict_types=1);

use OAuth2Framework\Component\NoneGrant\NoneResponseType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(NoneResponseType::class)
        ->args([service('oauth2_server.grant.none.authorization_storage')])
    ;
};
