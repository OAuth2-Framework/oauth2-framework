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

use OAuth2Framework\Bundle\Server\Service\SymfonyUserDiscovery;
use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery;
use function Fluent\create;
use function Fluent\get;

return [
    UserAccountDiscovery\LoginParameterChecker::class => create()
        ->tag('oauth2_server_user_account_discovery'),

    UserAccountDiscovery\PromptNoneParameterChecker::class => create()
        ->tag('oauth2_server_user_account_discovery'),

    UserAccountDiscovery\MaxAgeParameterChecker::class => create()
        ->tag('oauth2_server_user_account_discovery'),

    SymfonyUserDiscovery::class => create()
        ->arguments(
            get('security.token_storage'),
            get('security.authorization_checker')
        )
        ->tag('oauth2_server_user_account_discovery'),
];
