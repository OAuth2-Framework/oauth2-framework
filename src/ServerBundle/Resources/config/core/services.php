<?php

declare(strict_types=1);

use OAuth2Framework\ServerBundle\Routing\RouteLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
    ;

    $container->set(RouteLoader::class)
        ->tag('routing.loader')
    ;
};
