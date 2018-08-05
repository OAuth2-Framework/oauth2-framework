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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Event;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class InitialAccessTokenCreatedEvent extends Event
{
    private $initialAccessTokenId;
    private $expiresAt;
    private $userAccountId;

    public function __construct(InitialAccessTokenId $initialAccessTokenId, UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        $this->initialAccessTokenId = $initialAccessTokenId;
        $this->expiresAt = $expiresAt;
        $this->userAccountId = $userAccountId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema';
    }

    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getDomainId(): Id
    {
        return $this->getInitialAccessTokenId();
    }

    public function getPayload()
    {
        return [
            'user_account_id' => $this->userAccountId ? $this->userAccountId->getValue() : null,
            'expires_at' => $this->expiresAt ? $this->expiresAt->getTimestamp() : null,
        ];
    }
}
