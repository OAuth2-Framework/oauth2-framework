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

namespace OAuth2Framework\Component\JwtBearerGrant;

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
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->trustedIssuers);
    }

    /**
     * @param string $name
     *
     * @return TrustedIssuer
     */
    public function get(string $name): TrustedIssuer
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('The issuer with name "%s" is not known.', $name));
        }

        return $this->trustedIssuers[$name];
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->trustedIssuers);
    }
}
