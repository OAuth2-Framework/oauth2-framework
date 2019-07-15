<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\TokenEndpoint;

use Assert\Assertion;
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
     * @var null|ResourceOwnerId
     */
    private $resourceOwnerId;

    /**
     * @var null|Client
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

    public function hasClient(): bool
    {
        return null !== $this->client;
    }

    public function getClient(): Client
    {
        Assertion::notNull($this->client, 'internal_server_error');

        return $this->client;
    }

    public function setResourceOwnerId(ResourceOwnerId $resourceOwnerId): void
    {
        $this->resourceOwnerId = $resourceOwnerId;
    }

    public function hasResourceOwnerId(): bool
    {
        return null !== $this->resourceOwnerId;
    }

    public function getResourceOwnerId(): ResourceOwnerId
    {
        Assertion::notNull($this->resourceOwnerId, 'internal_server_error');

        return $this->resourceOwnerId;
    }
}
