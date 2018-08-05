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
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

class RefreshTokenCreatedEvent extends Event
{
    private $refreshTokenId;
    private $resourceOwnerId;
    private $clientId;
    private $parameter;
    private $expiresAt;
    private $metadata;
    private $resourceServerId;

    public function __construct(RefreshTokenId $refreshTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId)
    {
        $this->refreshTokenId = $refreshTokenId;
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameter = $parameters;
        $this->expiresAt = $expiresAt;
        $this->metadata = $metadatas;
        $this->resourceServerId = $resourceServerId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema';
    }

    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
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
        return $this->parameter;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getDomainId(): Id
    {
        return $this->getRefreshTokenId();
    }

    public function getPayload()
    {
        return [
            'resource_owner_id' => $this->resourceOwnerId->jsonSerialize(),
            'resource_owner_class' => \get_class($this->resourceOwnerId),
            'client_id' => $this->clientId->jsonSerialize(),
            'expires_at' => $this->expiresAt->getTimestamp(),
            'parameter' => (object) $this->parameter->all(),
            'metadata' => (object) $this->metadata->all(),
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }
}
