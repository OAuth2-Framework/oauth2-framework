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

use OAuth2Framework\Component\Server\Endpoint\UserInfo\ScopeSupport;
use function Fluent\create;

return [
    ScopeSupport\AddressScopeSupport::class => create()
        ->tag('oauth2_server_userinfo_scope_support'),

    ScopeSupport\EmailScopeSupport::class => create()
        ->tag('oauth2_server_userinfo_scope_support'),

    ScopeSupport\PhoneScopeSupport::class => create()
        ->tag('oauth2_server_userinfo_scope_support'),

    ScopeSupport\ProfilScopeSupport::class => create()
        ->tag('oauth2_server_userinfo_scope_support'),
];
