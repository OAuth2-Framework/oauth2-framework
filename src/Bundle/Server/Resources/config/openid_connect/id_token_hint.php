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

use OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery\IdTokenHintDiscovery;
use OAuth2Framework\Component\Server\Model\IdToken\IdTokenLoader;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountRepositoryInterface;
use function Fluent\create;
use function Fluent\get;

return [
    IdTokenLoader::class => create()
        ->arguments(
            get('jose.jwt_loader.id_token'),
            get('oauth2_server.openid_connect.id_token.key_set'),
            '%oauth2_server.openid_connect.id_token.signature_algorithms%'
        ),

    IdTokenHintDiscovery::class => create()
        ->arguments(
            get(IdTokenLoader::class),
            get(UserAccountRepositoryInterface::class)
        )
        ->tag('oauth2_server_user_account_discovery'),
];
