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

interface TrustedIssuerRepository
{
    /**
     * @param string $trustedIssuer
     *
     * @return null|TrustedIssuer
     */
    public function find(string $trustedIssuer): ?TrustedIssuer;
}
