<?php

declare(strict_types=1);

use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyDefaultRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(DefaultScopePolicy::class)
        ->args(['%oauth2_server.scope.policy.default.scope%'])
        ->tag('oauth2_server_scope_policy', [
            'policy_name' => 'default',
        ])
    ;

    $container->set(ScopePolicyDefaultRule::class)
        ->tag('oauth2_server_client_rule')
    ;
};
