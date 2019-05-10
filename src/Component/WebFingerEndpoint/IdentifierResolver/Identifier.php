<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

class Identifier
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var int|null
     */
    private $port;

    public function __construct(string $id, string $domain, ?int $port)
    {
        $this->id = $id;
        $this->domain = $domain;
        $this->port = $port;
    }

    public function getId(): string
    {
        return $this->id;
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
