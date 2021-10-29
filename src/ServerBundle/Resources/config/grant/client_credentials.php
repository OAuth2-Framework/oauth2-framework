<?php

declare(strict_types=1);

use OAuth2Framework\Component\ClientCredentialsGrant\ClientCredentialsGrantType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ClientCredentialsGrantType::class);
};
