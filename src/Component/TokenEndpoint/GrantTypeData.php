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
     *
     * @param Client|null $client
     */
    private function __construct(? Client $client)
    {
        $this->parameters = DataBag::create([]);
        $this->metadatas = DataBag::create([]);
        $this->client = $client;
    }

    /**
     * @param Client|null $client
     *
     * @return GrantTypeData
     */
    public static function create(? Client $client): self
    {
        return new self($client);
    }

    /**
     * @return DataBag
     */
    public function getMetadata(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @param string $key
     * @param mixed  $parameter
     *
     * @return GrantTypeData
     */
    public function withParameter(string $key, $parameter): self
    {
        $this->parameters = $this->parameters->with($key, $parameter);

        return $this;
    }

    /**
     * @return DataBag
     */
    public function getParameter(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @param Client $client
     *
     * @return GrantTypeData
     */
    public function withClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client|null
     */
    public function getClient(): ? Client
    {
        return $this->client;
    }

    /**
     * @param ResourceOwnerId $resourceOwnerId
     *
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
