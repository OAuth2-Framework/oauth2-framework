<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

interface RefreshToken
{
    public function addAccessToken(AccessTokenId $accessTokenId): static;

    /**
     * @return AccessTokenId[]
     */
    public function getAccessTokenIds(): iterable;

    public function getResponseData(): array;

    public function getId(): RefreshTokenId;

    public function getExpiresAt(): DateTimeImmutable;

    public function hasExpired(): bool;

    public function getExpiresIn(): int;

    public function getResourceOwnerId(): ResourceOwnerId;

    public function getClientId(): ClientId;

    public function getParameter(): DataBag;

    public function getMetadata(): DataBag;

    public function isRevoked(): bool;

    public function markAsRevoked(): static;

    public function getResourceServerId(): ?ResourceServerId;
}
