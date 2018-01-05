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

use OAuth2Framework\Bundle\Server\Service\MetadataBuilder;
use OAuth2Framework\Bundle\Server\Controller\MetadataController;
use OAuth2Framework\Component\Server\Middleware\Pipe;
use function Fluent\create;
use function Fluent\get;

return [
    'metadata_endpoint_pipe' => create(Pipe::class)
        ->arguments([
            get(MetadataController::class),
        ]),

    MetadataController::class => create()
        ->arguments(
            get('httplug.message_factory'),
            get(MetadataBuilder::class)
        ),

    MetadataBuilder::class => create()
        ->arguments(
            get('router')
        ),
];
