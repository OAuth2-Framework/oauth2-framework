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

use OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker\ScopeParameterChecker;
use OAuth2Framework\Component\Server\Model\Client\Rule;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use function Fluent\create;
use function Fluent\get;

return [
    Rule\ScopeRule::class => create()
        ->arguments(
            get(ScopeRepositoryInterface::class)
        )
        ->tag('oauth2_server_client_rule'),

    ScopeParameterChecker::class => create()
        ->arguments(
            get(ScopeRepositoryInterface::class),
            get(ScopePolicyManager::class)->nullIfMissing()
        )
        ->tag('oauth2_server_authorization_parameter_checker'),
];
