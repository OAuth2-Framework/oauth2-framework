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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface AuthorizationCode extends \JsonSerializable
{
    public function isUsed(): bool;

    public function markAsUsed(): void;

    public function getQueryParameters(): array;

    /**
     * @return mixed
     */
    public function getQueryParameter(string $key);

    public function hasQueryParameter(string $key): bool;

    public function getRedirectUri(): string;

    public function toArray(): array;

    public function getId(): AuthorizationCodeId;

    public function getExpiresAt(): \DateTimeImmutable;

    public function hasExpired(): bool;

    public function getUserAccountId(): UserAccountId;

    public function getClientId(): ClientId;

    public function getParameter(): DataBag;

    public function getMetadata(): DataBag;

    public function getResourceServerId(): ?ResourceServerId;

    public function getExpiresIn(): int;
}
