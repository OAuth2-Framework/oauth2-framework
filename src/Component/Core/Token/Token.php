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

namespace OAuth2Framework\Component\Core\Token;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

abstract class Token implements \JsonSerializable
{
    protected $tokenId;
    private $expiresAt;
    private $resourceOwnerId;
    private $clientId;
    private $parameter;
    private $metadata;
    private $revoked;
    private $resourceServerId;

    public function __construct(TokenId $tokenId, ClientId $clientId, ResourceOwnerId $resourceOwnerId, DataBag $parameter, DataBag $metadata, \DateTimeImmutable $expiresAt, ?ResourceServerId $resourceServerId)
    {
        $this->tokenId = $tokenId;
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
        $this->revoked = false;
    }

    public function getTokenId(): TokenId
    {
        return $this->tokenId;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < \time();
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

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): void
    {
        $this->revoked = true;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getExpiresIn(): int
    {
        $expiresAt = $this->expiresAt;
        if (null === $expiresAt) {
            return 0;
        }

        return (int) ($this->expiresAt->getTimestamp() - \time() < 0 ? 0 : $this->expiresAt->getTimestamp() - \time());
    }

    public function jsonSerialize()
    {
        $data = [
            'expires_at' => $this->getExpiresAt()->getTimestamp(),
            'client_id' => $this->getClientId()->getValue(),
            'parameters' => (object) $this->getParameter()->all(),
            'metadatas' => (object) $this->getMetadata()->all(),
            'is_revoked' => $this->isRevoked(),
            'resource_owner_id' => $this->getResourceOwnerId()->getValue(),
            'resource_owner_class' => \get_class($this->getResourceOwnerId()),
            'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }
}
