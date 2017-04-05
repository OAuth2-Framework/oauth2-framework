<?php

declare(strict_types = 1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

use OAuth2Framework\Component\Server\Model\Scope\DefaultScopePolicy;
use OAuth2Framework\Component\Server\Model\Client\Rule\ScopePolicyDefaultRule;
use function Fluent\create;

return [
    DefaultScopePolicy::class => create()
        ->arguments(
            '%oauth2_server.scope.policy.default.scope%'
        )
        ->tag('oauth2_server_scope_policy', ['policy_name' => 'default']),

    ScopePolicyDefaultRule::class => create()
        ->tag('oauth2_server_client_rule'),
];
