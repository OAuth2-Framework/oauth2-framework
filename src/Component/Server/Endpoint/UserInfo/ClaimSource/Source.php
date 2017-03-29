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

namespace OAuth2Framework\Component\Server\Endpoint\UserInfo\ClaimSource;

use Assert\Assertion;

final class Source
{
    /**
     * @var string[]
     */
    private $availableClaims = [];

    /**
     * @var array
     */
    private $source = [];

    /**
     * Source constructor.
     *
     * @param string[] $availableClaims
     * @param array    $source
     */
    public function __construct(array $availableClaims, array $source)
    {
        Assertion::notEmpty($availableClaims);
        Assertion::notEmpty($source);
        $this->availableClaims = $availableClaims;
        $this->source = $source;
    }

    /**
     * @return string[]
     */
    public function getAvailableClaims(): array
    {
        return $this->availableClaims;
    }

    /**
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }
}
