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

use OAuth2Framework\Component\Model\IdToken\IdTokenLoader;
use function Fluent\create;
use function Fluent\get;

return [
    IdTokenLoader::class => create()
        ->arguments(
            get('jose.jws_loader.oauth2_server.openid_connect.id_token.signature'),
            get('jose.key_set.oauth2_server.openid_connect.id_token'),
            '%oauth2_server.openid_connect.id_token.signature_algorithms%'
        ),
];
