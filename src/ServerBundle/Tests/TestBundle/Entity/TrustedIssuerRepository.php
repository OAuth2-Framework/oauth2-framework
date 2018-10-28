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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuer;
use OAuth2Framework\Component\Core\TrustedIssuer\TrustedIssuerRepository as TrustedIssuerRepositoryInterface;

final class TrustedIssuerRepository implements TrustedIssuerRepositoryInterface
{
    public function find(string $trustedIssuer): ?TrustedIssuer
    {
        return null;
    }
}
