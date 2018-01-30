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

use OAuth2Framework\Bundle\Routing\RouteLoader;
use function Fluent\create;

return [
    RouteLoader::class => create()
        ->tag('routing.loader'),
];
