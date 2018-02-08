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

namespace OAuth2Framework\Component\RefreshTokenGrant\Event;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Domain\DomainObject;

class RefreshTokenCreatedEvent extends Event
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

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
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var DataBag
     */
    private $metadatas;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    /**
     * RefreshTokenCreatedEvent constructor.
     *
     * @param RefreshTokenId        $refreshTokenId
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param \DateTimeImmutable    $expiresAt
     * @param ResourceServerId|null $resourceServerId
     */
    protected function __construct(RefreshTokenId $refreshTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId)
    {
        $this->refreshTokenId = $refreshTokenId;
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->expiresAt = $expiresAt;
        $this->metadatas = $metadatas;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema';
    }

    /**
     * @param RefreshTokenId        $refreshTokenId
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param \DateTimeImmutable    $expiresAt
     * @param ResourceServerId|null $resourceServerId
     *
     * @return RefreshTokenCreatedEvent
     */
    public static function create(RefreshTokenId $refreshTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId): self
    {
        return new self($refreshTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
    }

    /**
     * @return RefreshTokenId
     */
    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    /**
     * @return ResourceOwnerId
     */
    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return DataBag
     */
    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return DataBag
     */
    public function getMetadatas(): DataBag
    {
        return $this->metadatas;
    }

    /**
     * @return ResourceServerId
     */
    public function getResourceServerId(): ? ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getRefreshTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'resource_owner_id' => $this->resourceOwnerId->jsonSerialize(),
            'resource_owner_class' => get_class($this->resourceOwnerId),
            'client_id' => $this->clientId->jsonSerialize(),
            'parameters' => (object) $this->parameters->all(),
            'expires_at' => $this->expiresAt->getTimestamp(),
            'metadatas' => (object) $this->metadatas->all(),
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $refreshTokenId = RefreshTokenId::create($json->domain_id);
        $resourceOwnerClass = $json->payload->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->payload->resource_owner_id);
        $clientId = ClientId::create($json->payload->client_id);
        $parameters = DataBag::create((array) $json->payload->parameters);
        $metadatas = DataBag::create((array) $json->payload->metadatas);
        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);
        $resourceServerId = null !== $json->payload->resource_server_id ? ResourceServerId::create($json->payload->resource_server_id) : null;

        return new self($refreshTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
    }
}
