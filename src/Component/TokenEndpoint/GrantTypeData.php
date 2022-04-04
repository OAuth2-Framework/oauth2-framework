<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint;

use Assert\Assertion;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

class GrantTypeData
{
    private DataBag $metadata;

    private DataBag $parameter;

    private ?ResourceOwnerId $resourceOwnerId = null;

    public function __construct(
        private ?Client $client
    ) {
        $this->parameter = new DataBag([]);
        $this->metadata = new DataBag([]);
    }

    public static function create(?Client $client): static
    {
        return new self($client);
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getParameter(): DataBag
    {
        return $this->parameter;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function hasClient(): bool
    {
        return $this->client !== null;
    }

    public function getClient(): Client
    {
        Assertion::notNull($this->client, 'internal_server_error');

        return $this->client;
    }

    public function setResourceOwnerId(ResourceOwnerId $resourceOwnerId): static
    {
        $this->resourceOwnerId = $resourceOwnerId;

        return $this;
    }

    public function hasResourceOwnerId(): bool
    {
        return $this->resourceOwnerId !== null;
    }

    public function getResourceOwnerId(): ResourceOwnerId
    {
        Assertion::notNull($this->resourceOwnerId, 'internal_server_error');

        return $this->resourceOwnerId;
    }
}
