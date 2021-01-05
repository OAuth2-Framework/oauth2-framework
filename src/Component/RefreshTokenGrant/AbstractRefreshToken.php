<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
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
    private array $accessTokenIds = [];

    private \DateTimeImmutable $expiresAt;

    private ResourceOwnerId $resourceOwnerId;

    private ClientId $clientId;

    private DataBag $parameter;

    private DataBag $metadata;

    private bool $revoked;

    private ?ResourceServerId $resourceServerId;

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
        return $this->expiresAt->getTimestamp() < time();
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
        return $this->expiresAt->getTimestamp() - time() < 0 ? 0 : $this->expiresAt->getTimestamp() - time();
    }
}
