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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use OAuth2Framework\Component\ClientRegistrationEndpoint\Event as InitialAccessTokenEvent;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class InitialAccessToken implements DomainObject
{
    private $revoked;
    private $initialAccessTokenId;
    private $expiresAt;
    private $userAccountId;

    public function __construct(InitialAccessTokenId $initialAccessTokenId, UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt)
    {
        $this->initialAccessTokenId = $initialAccessTokenId;
        $this->expiresAt = $expiresAt;
        $this->userAccountId = $userAccountId;
        $this->revoked = false;
    }

    public function getTokenId(): InitialAccessTokenId
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

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < \time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): void
    {
        $this->revoked = true;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema';
    }

    public function jsonSerialize()
    {
        $data = [
            '$schema' => $this->getSchema(),
            'type' => \get_class($this),
            'initial_access_token_id' => $this->getTokenId() ? $this->getTokenId()->getValue() : null,
            'user_account_id' => $this->getUserAccountId() ? $this->getUserAccountId()->getValue() : null,
            'expires_at' => $this->getExpiresAt() ? $this->getExpiresAt()->getTimestamp() : null,
            'is_revoked' => $this->isRevoked(),
        ];

        return $data;
    }

    public function apply(Event $event): void
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if ($this->initialAccessTokenId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this initial access token.');
        }
        $method = $map[$event->getType()];
        $this->$method($event);
    }

    private function getEventMap(): array
    {
        return [
            InitialAccessTokenEvent\InitialAccessTokenCreatedEvent::class => 'applyInitialAccessTokenCreatedEvent',
            InitialAccessTokenEvent\InitialAccessTokenRevokedEvent::class => 'applyInitialAccessTokenRevokedEvent',
        ];
    }

    protected function applyInitialAccessTokenCreatedEvent(InitialAccessTokenEvent\InitialAccessTokenCreatedEvent $event): void
    {
        $this->initialAccessTokenId = $event->getInitialAccessTokenId();
        $this->expiresAt = $event->getExpiresAt();
        $this->userAccountId = $event->getUserAccountId();
    }

    protected function applyInitialAccessTokenRevokedEvent(InitialAccessTokenEvent\InitialAccessTokenRevokedEvent $event): void
    {
        $this->revoked = true;
    }
}
