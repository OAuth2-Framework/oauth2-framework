<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\Rule\SectorIdentifierUriRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(SectorIdentifierUriRule::class)
        ->args([service('oauth2_server.http_client')])
    ;
};
