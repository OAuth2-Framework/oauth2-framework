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

use OAuth2Framework\Component\Server\GrantType\GrantTypeManager;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeManager;
use function Fluent\create;

return [
    GrantTypeManager::class => create(),
    ResponseTypeManager::class => create(),
];
