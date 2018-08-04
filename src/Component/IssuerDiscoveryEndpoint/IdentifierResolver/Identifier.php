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

class Identifier
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var int|null
     */
    private $port;

    /**
     * Identifier constructor.
     *
     * @param null|int $port
     */
    public function __construct(string $username, string $domain, ?int $port)
    {
        $this->username = $username;
        $this->domain = $domain;
        $this->port = $port;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }
}
