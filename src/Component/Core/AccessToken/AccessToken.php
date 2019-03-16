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

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

interface AccessToken extends \JsonSerializable
{
    public function getId(): AccessTokenId;

    public function getExpiresAt(): \DateTimeImmutable;

    public function hasExpired(): bool;

    public function getExpiresIn(): int;

    public function getResourceOwnerId(): ResourceOwnerId;

    public function getClientId(): ClientId;

    public function getParameter(): DataBag;

    public function getMetadata(): DataBag;

    public function isRevoked(): bool;

    public function markAsRevoked(): void;

    public function getResourceServerId(): ?ResourceServerId;

    public function getResponseData(): array;
}
