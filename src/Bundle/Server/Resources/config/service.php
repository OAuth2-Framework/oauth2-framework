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

use function Fluent\create;
use function Fluent\get;
use Jose\JWTLoaderInterface;
use Jose\JWTCreatorInterface;
use Jose\Signer;
use Jose\Verifier;
use Jose\Checker\CheckerManagerInterface;

return [
    \Interop\Http\Factory\ResponseFactoryInterface::class => create(\Http\Factory\Diactoros\ResponseFactory::class),

    \Interop\Http\Factory\UriFactoryInterface::class => create(\Http\Factory\Diactoros\UriFactory::class),

    // FIXME
    JWTLoaderInterface::class => create(\Jose\JWTLoader::class)
        ->arguments(
            get(CheckerManagerInterface::class),
            get(Verifier::class)
        ),

    // FIXME
    JWTCreatorInterface::class => create(\Jose\JWTCreator::class)
        ->arguments(
            get(Signer::class)
        ),

    // FIXME
    Signer::class => create()
        ->arguments(
            ['RS256', 'HS256']
        ),

    // FIXME
    Verifier::class => create()
        ->arguments(
            ['RS256', 'HS256']
        ),

    // FIXME
    CheckerManagerInterface::class => create(\Jose\Checker\CheckerManager::class),
];
