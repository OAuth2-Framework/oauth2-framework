<?php

declare(strict_types=1);

use OAuth2Framework\Component\Scope\Rule\ScopePolicyRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(ScopePolicyRule::class);
};
