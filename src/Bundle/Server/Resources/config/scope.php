<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Bundle\Server\Model\ScopeRepository;
use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Model\Scope;
use function Fluent\create;
use function Fluent\get;

return [
    ScopeRepository::class => create()
        ->arguments(
            ['openid', 'phone', 'email', 'address', 'profile', 'offline_access'] // Fixme
        ),

    // This scope policy is added by default
    //Scope\NoScopePolicy::class => create()
    //    ->tag('oauth2_server_scope_policy', ['policy_name' => 'none']),

    Scope\DefaultScopePolicy::class => create()
        ->arguments(
            [] //FIXME
        )
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'default']),
    Scope\ErrorScopePolicy::class => create()
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'error']),

    Rule\ScopeRule::class => create()
        ->arguments(
            get(ScopeRepository::class)
        )
        ->tag('oauth2_server_client_rule'),
];
