<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface AuthorizationCode
{
    public function isUsed(): bool;

    public function markAsUsed(): void;

    public function isRevoked(): bool;

    public function markAsRevoked(): void;

    public function getQueryParameters(): array;

    public function getQueryParameter(string $key): mixed;

    public function hasQueryParameter(string $key): bool;

    public function getRedirectUri(): string;

    public function toArray(): array;

    public function getId(): AuthorizationCodeId;

    public function getExpiresAt(): DateTimeImmutable;

    public function hasExpired(): bool;

    public function getUserAccountId(): UserAccountId;

    public function getClientId(): ClientId;

    public function getParameter(): DataBag;

    public function getMetadata(): DataBag;

    public function getResourceServerId(): ?ResourceServerId;

    public function getExpiresIn(): int;
}
