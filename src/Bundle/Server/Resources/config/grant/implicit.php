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

use OAuth2Framework\Component\Server\ResponseType\TokenResponseType;
use function Fluent\create;
use function Fluent\get;

return [
    TokenResponseType::class => create()
        ->arguments(
            get('command_bus')
        )
        ->tag('oauth2_server_response_type'),
];
