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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\IssuerDiscoveryEndpoint\Resource;

class ResourceObject implements Resource
{
    /**
     * @var string
     */
    private $issuer;

    /**
     * Resource constructor.
     *
     * @param string $issuer
     */
    public function __construct(string $issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * {@inheritdoc}
     */
    public function getIssuer(): string
    {
        return $this->issuer;
    }
}
