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

namespace OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver;

interface IdentifierResolver
{
    /**
     * @param string $resource_name
     *
     * @return bool
     */
    public function supports(string $resource_name): bool;

    /**
     * @param string $resource_name
     *
     * @return Identifier
     */
    public function resolve(string $resource_name): Identifier;
}
