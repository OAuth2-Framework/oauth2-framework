<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

abstract class AbstractRefreshToken implements RefreshToken
{
    /**
     * @var AccessTokenId[]
     */
    private $accessTokenIds = [];

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
    private $parameter;

    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var bool
     */
    private $revoked;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    public function __construct(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        $this->resourceOwnerId = $resourceOwnerId;
        $this->clientId = $clientId;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
        $this->revoked = false;
    }

    public function addAccessToken(AccessTokenId $accessTokenId): void
    {
        $id = $accessTokenId->getValue();
        if (!\array_key_exists($id, $this->accessTokenIds)) {
            $this->accessTokenIds[$id] = $accessTokenId;
        }
    }

    /**
     * @return AccessTokenId[]
     */
    public function getAccessTokenIds(): array
    {
        return $this->accessTokenIds;
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter();
        $data->set('access_token', $this->getId()->getValue());
        $data->set('expires_in', $this->getExpiresIn());
        $data->set('refresh_token', $this->getId());

        return $data->all();
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
        return $this->expiresAt->getTimestamp() - \time() < 0 ? 0 : $this->expiresAt->getTimestamp() - \time();
    }

    public function jsonSerialize(): array
    {
        $data = [
            'refresh_token_id' => $this->getId()->getValue(),
            'access_token_ids' => \array_keys($this->getAccessTokenIds()),
            'resource_server_id' => null !== $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
            'expires_at' => $this->getExpiresAt()->getTimestamp(),
            'client_id' => $this->getClientId()->getValue(),
            'parameters' => (object) $this->getParameter()->all(),
            'metadatas' => (object) $this->getMetadata()->all(),
            'is_revoked' => $this->isRevoked(),
            'resource_owner_id' => $this->getResourceOwnerId()->getValue(),
            'resource_owner_class' => \get_class($this->getResourceOwnerId()),
        ];

        return $data;
    }
}
