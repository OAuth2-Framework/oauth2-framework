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

use OAuth2Framework\Component\Endpoint\Authorization\UserAccountDiscovery\IdTokenHintDiscovery;
use OAuth2Framework\Component\Model\IdToken\IdTokenLoader;
use function Fluent\create;
use function Fluent\get;

return [
    IdTokenLoader::class => create()
        ->arguments(
            get('jose.jws_loader.id_token'),
            get('jose.key_set.oauth2_server.key_set.signature'),
            '%oauth2_server.openid_connect.id_token.signature_algorithms%'
        ),

    IdTokenHintDiscovery::class => create()
        ->arguments(
            get(IdTokenLoader::class),
            get('oauth2_server.user_account_repository')
        )
        ->tag('oauth2_server_user_account_discovery'),
];
