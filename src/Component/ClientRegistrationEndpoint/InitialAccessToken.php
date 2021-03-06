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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface InitialAccessToken
{
    public function getId(): InitialAccessTokenId;

    public function getUserAccountId(): ?UserAccountId;

    public function getExpiresAt(): ?\DateTimeImmutable;

    public function hasExpired(): bool;

    public function isRevoked(): bool;

    public function markAsRevoked(): void;
}
