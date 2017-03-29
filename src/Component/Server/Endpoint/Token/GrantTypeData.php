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

namespace OAuth2Framework\Component\Server\Endpoint\Token;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;

final class GrantTypeData
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
     * @var ResourceOwnerId
     */
    private $resourceOwnerId;

    /**
     * @var Client|null
     */
    private $client;

    /**
     * @var string[]
     */
    private $scopes = [];

    /**
     * @var bool
     */
    private $issueRefreshToken = false;

    /**
     * @var string[]|null
     */
    private $availableScopes = null;

    /**
     * GrantTypeData constructor.
     *
     * @param Client|null $client
     */
    private function __construct(?Client $client)
    {
        $this->parameters = new DataBag();
        $this->metadatas = new DataBag();
        $this->client = $client;
    }

    /**
     * @param Client|null $client
     *
     * @return GrantTypeData
     */
    public static function create(?Client $client): GrantTypeData
    {
        return new self($client);
    }

    /**
     * @param string $key
     * @param mixed  $metadata
     *
     * @return GrantTypeData
     */
    public function withMetadata(string $key, $metadata): GrantTypeData
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
        Assertion::true($this->hasMetadata($key), sprintf('The metadata with key \'%s\' does not exist.', $key));

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
     * @param $parameter
     *
     * @return GrantTypeData
     */
    public function withParameter(string $key, $parameter): GrantTypeData
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
        Assertion::true($this->hasParameter($key), sprintf('The parameter with key \'%s\' does not exist.', $key));

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
    public function withClient(Client $client): GrantTypeData
    {
        $clone = clone $this;
        $clone->client = $client;

        return $clone;
    }

    /**
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * @param ResourceOwnerId $resourceOwnerId
     *
     * @return GrantTypeData
     */
    public function withResourceOwnerId(ResourceOwnerId $resourceOwnerId): GrantTypeData
    {
        $clone = clone $this;
        $clone->resourceOwnerId = $resourceOwnerId;

        return $clone;
    }

    /**
     * @return ResourceOwnerId
     */
    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }

    /**
     * @param string[] $scopes
     *
     * @return GrantTypeData
     */
    public function withScopes(array $scopes): GrantTypeData
    {
        $clone = clone $this;
        $clone->scopes = $scopes;

        return $clone;
    }

    /**
     * @param string $scope
     *
     * @return GrantTypeData
     */
    public function withScope(string $scope): GrantTypeData
    {
        if ($this->hasScope($scope)) {
            return $this;
        }
        $clone = clone $this;
        $clone->scopes[] = $scope;

        return $clone;
    }

    /**
     * @param string $scope
     *
     * @return GrantTypeData
     */
    public function withoutScope(string $scope): GrantTypeData
    {
        if (!$this->hasScope($scope)) {
            return $this;
        }
        $clone = clone $this;
        $key = array_search($scope, $clone->scopes);
        unset($clone->scopes[$key]);

        return $clone;
    }

    /**
     * @param string $scope
     *
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes);
    }

    /**
     * @return string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return bool
     */
    public function hasRefreshToken(): bool
    {
        return $this->issueRefreshToken;
    }

    /**
     * @return GrantTypeData
     */
    public function withRefreshToken(): GrantTypeData
    {
        if (true === $this->issueRefreshToken) {
            return $this;
        }
        $clone = clone $this;
        $clone->issueRefreshToken = true;

        return $clone;
    }

    /**
     * @return GrantTypeData
     */
    public function withoutRefreshToken(): GrantTypeData
    {
        if (false === $this->issueRefreshToken) {
            return $this;
        }
        $clone = clone $this;
        $clone->issueRefreshToken = false;

        return $clone;
    }

    /**
     * @return string[]|null
     */
    public function getAvailableScopes(): ?array
    {
        return $this->availableScopes;
    }

    /**
     * @param string[] $scopes
     *
     * @return GrantTypeData
     */
    public function withAvailableScopes(array $scopes): GrantTypeData
    {
        $clone = clone $this;
        $clone->availableScopes = $scopes;

        return $clone;
    }
}
