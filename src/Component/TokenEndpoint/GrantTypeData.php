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
     * @param string $key
     * @param mixed  $metadata
     *
     * @return GrantTypeData
     */
    public function withMetadata(string $key, $metadata): self
    {
        $clone = clone $this;
        $clone->metadatas = $this->metadatas->with($key, $metadata);

        return $clone;
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getMetadata(string $key)
    {
        if (!$this->hasMetadata($key)) {
            throw new \InvalidArgumentException(sprintf('The metadata with key "%s" does not exist.', $key));
        }

        return $this->metadatas->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasMetadata(string $key): bool
    {
        return $this->metadatas->has($key);
    }

    /**
     * @param string $key
     * @param mixed  $parameter
     *
     * @return GrantTypeData
     */
    public function withParameter(string $key, $parameter): self
    {
        $clone = clone $this;
        $clone->parameters = $this->parameters->with($key, $parameter);

        return $clone;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getParameter(string $key)
    {
        if (!$this->hasParameter($key)) {
            throw new \InvalidArgumentException(sprintf('The parameter with key "%s" does not exist.', $key));
        }

        return $this->parameters->get($key);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasParameter(string $key): bool
    {
        return $this->parameters->has($key);
    }

    /**
     * @param Client $client
     *
     * @return GrantTypeData
     */
    public function withClient(Client $client): self
    {
        $clone = clone $this;
        $clone->client = $client;

        return $clone;
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
        $clone = clone $this;
        $clone->resourceOwnerId = $resourceOwnerId;

        return $clone;
    }

    /**
     * @return null|ResourceOwnerId
     */
    public function getResourceOwnerId(): ?ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }
}
