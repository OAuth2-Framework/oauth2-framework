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

namespace OAuth2Framework\Component\TokenEndpoint;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

class GrantTypeData
{
    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var DataBag
     */
    private $parameter;

    /**
     * @var ResourceOwnerId|null
     */
    private $resourceOwnerId;

    /**
     * @var Client|null
     */
    private $client;

    public function __construct(?Client $client)
    {
        $this->parameter = new DataBag([]);
        $this->metadata = new DataBag([]);
        $this->client = $client;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getParameter(): DataBag
    {
        return $this->parameter;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setResourceOwnerId(ResourceOwnerId $resourceOwnerId): void
    {
        $this->resourceOwnerId = $resourceOwnerId;
    }

    public function getResourceOwnerId(): ?ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }
}
