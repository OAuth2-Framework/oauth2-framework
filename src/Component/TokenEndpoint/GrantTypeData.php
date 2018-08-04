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
    private $metadatas;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var ResourceOwnerId|null
     */
    private $resourceOwnerId;

    /**
     * @var Client|null
     */
    private $client;

    /**
     * GrantTypeData constructor.
     */
    private function __construct(?Client $client)
    {
        $this->parameters = new DataBag([]);
        $this->metadatas = new DataBag([]);
        $this->client = $client;
    }

    /**
     * @return GrantTypeData
     */
    public static function create(?Client $client): self
    {
        return new self($client);
    }

    public function getMetadata(): DataBag
    {
        return $this->metadatas;
    }

    public function getParameter(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return GrantTypeData
     */
    public function withClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @return GrantTypeData
     */
    public function withResourceOwnerId(ResourceOwnerId $resourceOwnerId): self
    {
        $this->resourceOwnerId = $resourceOwnerId;

        return $this;
    }

    /**
     * @return null|ResourceOwnerId
     */
    public function getResourceOwnerId(): ?ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }
}
