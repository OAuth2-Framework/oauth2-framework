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

use OAuth2Framework\Component\Server\Model\Scope\NoScopePolicy;
use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use function Fluent\create;
use function Fluent\get;

return [
    ScopePolicyManager::class => create(),

    NoScopePolicy::class => create()
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'none']),

    Rule\ScopePolicyRule::class => create()
        ->arguments(
            get(ScopePolicyManager::class)
        )
        ->tag('oauth2_server_client_rule'),
];
