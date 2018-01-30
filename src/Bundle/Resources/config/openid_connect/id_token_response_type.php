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

use OAuth2Framework\Component\Model\IdToken\IdTokenBuilderFactory;
use OAuth2Framework\Component\ResponseType\IdTokenResponseType;
use function Fluent\create;
use function Fluent\get;

return [
    IdTokenResponseType::class => create()
        ->arguments(
            get(IdTokenBuilderFactory::class),
            '%oauth2_server.openid_connect.id_token.default_signature_algorithm%',
            get('jose.jws_builder.id_token'),
            get('jose.key_set.oauth2_server.key_set.signature'),
            get('jose.encrypter.id_token')->nullIfMissing()
        )
        ->tag('oauth2_server_response_type'),
];
