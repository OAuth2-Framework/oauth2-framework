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

namespace OAuth2Framework\Component\Server\Model\TrustedIssuer;

use Jose\Object\JWKSetInterface;

interface TrustedIssuerInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string[]
     */
    public function getAllowedSignatureAlgorithms(): array;

    /**
     * @return JWKSetInterface
     */
    public function getSignatureKeys(): JWKSetInterface;
}
