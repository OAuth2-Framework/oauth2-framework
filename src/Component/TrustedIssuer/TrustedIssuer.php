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

namespace OAuth2Framework\Component\TrustedIssuer;

use Jose\Component\Core\JWKSet;

interface TrustedIssuer
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string[]
     */
    public function getAllowedAssertionTypes(): array;

    /**
     * @return string[]
     */
    public function getAllowedSignatureAlgorithms(): array;

    /**
     * @return JWKSet
     */
    public function getJWKSet(): JWKSet;
}
