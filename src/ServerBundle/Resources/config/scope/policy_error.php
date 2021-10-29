<?php

declare(strict_types=1);

use OAuth2Framework\Component\Scope\Policy\ErrorScopePolicy;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(ErrorScopePolicy::class)
        ->args(['%oauth2_server.scope.policy.default.scope%'])
        ->tag('oauth2_server_scope_policy', [
            'policy_name' => 'error',
        ])
    ;
};
