<?php

declare(strict_types=1);

use OAuth2Framework\Component\ResourceServerAuthentication\AuthenticationMethodManager;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(AuthenticationMethodManager::class);
};
