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

use Jose\Component\Core\JWKSet;

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
     * @return JWKSet
     */
    public function getSignatureKeys(): JWKSet;
}
