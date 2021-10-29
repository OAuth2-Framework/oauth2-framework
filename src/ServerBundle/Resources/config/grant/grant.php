<?php

declare(strict_types=1);

use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseTypeManager;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeManager;
use OAuth2Framework\Component\TokenEndpoint\Rule\GrantTypesRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(GrantTypeManager::class);
    $container->set(ResponseTypeManager::class);
    $container->set(GrantTypesRule::class);
};
