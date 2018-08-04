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

namespace OAuth2Framework\Component\Core\AccessToken\Event;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

class AccessTokenCreatedEvent extends Event
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var ResourceOwnerId
     */
    private $resourceOwnerId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var DataBag
     */
    private $metadatas;

    /**
     * @var null|ResourceServerId
     */
    private $resourceServerId;

    /**
     * AccessTokenCreatedEvent constructor.
     */
    protected function __construct(AccessTokenId $accessTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId)
    {
        $this->accessTokenId = $accessTokenId;
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->metadatas = $metadatas;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/created/1.0/schema';
    }

    /**
     * @return AccessTokenCreatedEvent
     */
    public static function create(AccessTokenId $accessTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId): self
    {
        return new self($accessTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $accessTokenId = new AccessTokenId($json->domain_id);
        $resourceOwnerClass = $json->payload->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->payload->resource_owner_id);
        $clientId = new ClientId($json->payload->client_id);
        $parameters = new DataBag((array) $json->payload->parameters);
        $metadatas = new DataBag((array) $json->payload->metadatas);
        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);
        $resourceServerId = null !== $json->payload->resource_server_id ? new ResourceServerId($json->payload->resource_server_id) : null;

        return new self($accessTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
    }

    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getParameter(): DataBag
    {
        return $this->parameters;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @return null|ResourceServerId
     */
    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAccessTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'resource_owner_id' => $this->resourceOwnerId->getValue(),
            'resource_owner_class' => \get_class($this->resourceOwnerId),
            'client_id' => $this->clientId->getValue(),
            'parameters' => (object) $this->parameters->all(),
            'metadatas' => (object) $this->metadatas->all(),
            'expires_at' => $this->expiresAt->getTimestamp(),
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }
}
