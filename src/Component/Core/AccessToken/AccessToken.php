<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

interface AccessToken
{
    public function getId(): AccessTokenId;

    public function getExpiresAt(): DateTimeImmutable;

    public function hasExpired(): bool;

    public function getExpiresIn(): int;

    public function getResourceOwnerId(): ResourceOwnerId;

    public function getClientId(): ClientId;

    public function getParameter(): DataBag;

    public function getMetadata(): DataBag;

    public function isRevoked(): bool;

    public function markAsRevoked(): self;

    public function getResourceServerId(): ?ResourceServerId;

    public function getResponseData(): array;
}
