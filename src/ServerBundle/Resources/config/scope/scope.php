<?php

declare(strict_types=1);

use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Scope\Rule\ScopeRule;
use OAuth2Framework\Component\Scope\ScopeParameterChecker;
use OAuth2Framework\Component\Scope\ScopeRepository;
use OAuth2Framework\Component\Scope\TokenEndpointScopeExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
    ;

    $container->set(ScopePolicyManager::class);

    $container->set(NoScopePolicy::class)
        ->tag('oauth2_server_scope_policy', [
            'policy_name' => 'none',
        ])
    ;

    $container->set(ScopeRule::class);

    $container->set(ScopeParameterChecker::class)
        ->args([service(ScopeRepository::class), service(ScopePolicyManager::class)])
    ;

    $container->set(TokenEndpointScopeExtension::class)
        ->args([service(ScopeRepository::class), service(ScopePolicyManager::class)])
    ;
};
