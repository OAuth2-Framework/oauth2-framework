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

namespace OAuth2Framework\Component\ClientAuthentication;

final class TrustedIssuerManager
{
    /**
     * @var TrustedIssuer[]
     */
    private $trustedIssuers = [];

    /**
     * @param TrustedIssuer $trustedIssuer
     */
    public function add(TrustedIssuer $trustedIssuer)
    {
        $this->trustedIssuers[$trustedIssuer->name()] = $trustedIssuer;
    }

    /**
     * @param string $issuer
     *
     * @return bool
     */
    public function has(string $issuer): bool
    {
        return array_key_exists($issuer, $this->trustedIssuers);
    }

    /**
     * @param string $issuer
     *
     * @return TrustedIssuer
     */
    public function get(string $issuer): TrustedIssuer
    {
        if (!$this->has($issuer)) {
            throw new \InvalidArgumentException(sprintf('The issuer "%s" is not known.', $issuer));
        }

        return $this->trustedIssuers[$issuer];
    }
}
