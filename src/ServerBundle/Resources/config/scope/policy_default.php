<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Scope\Policy\DefaultScopePolicy;
use OAuth2Framework\Component\Scope\Rule\ScopePolicyDefaultRule;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $container) {
    $container = $container->services()->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(DefaultScopePolicy::class)
        ->args([
            '%oauth2_server.scope.policy.default.scope%',
        ])
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'default'])
    ;

    $container->set(ScopePolicyDefaultRule::class)
        ->tag('oauth2_server_client_rule')
    ;
};
