<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use OAuth2Framework\Component\Scope\Rule\ScopeRule;
use OAuth2Framework\Component\Scope\ScopeParameterChecker;
use OAuth2Framework\Component\Scope\TokenEndpointScopeExtension;
use OAuth2Framework\Component\Scope\Policy\NoScopePolicy;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure();

    $container->set(ScopePolicyManager::class);

    $container->set(NoScopePolicy::class)
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'none']);

    $container->set(ScopeRule::class);

    $container->set(ScopeParameterChecker::class)
        ->args([
            ref('oauth2_server.scope.repository'),
            ref(ScopePolicyManager::class),
        ]);

    $container->set(TokenEndpointScopeExtension::class)
        ->args([
            ref('oauth2_server.scope.repository'),
            ref(ScopePolicyManager::class),
        ]);
};
