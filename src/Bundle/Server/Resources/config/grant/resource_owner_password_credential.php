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

use OAuth2Framework\Component\Server\GrantType\ResourceOwnerPasswordCredentialsGrantType;
use function Fluent\create;
use function Fluent\get;

return [
    ResourceOwnerPasswordCredentialsGrantType::class => create()
        ->arguments(
            get('oauth2_server.user_account.manager'),
            get('oauth2_server.user_account.repository'),
            '%oauth2_server.grant.resource_owner_password_credential.issue_refresh_token%',
            '%oauth2_server.grant.resource_owner_password_credential.issue_refresh_token_for_public_clients%'
        )
        ->tag('oauth2_server_grant_type'),
];
