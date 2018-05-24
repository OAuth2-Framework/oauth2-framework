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

use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Event as InitialAccessTokenEvent;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

class InitialAccessToken implements ContainsRecordedMessages, DomainObject
{
    use PrivateMessageRecorderCapabilities;

    /**
     * @var bool
     */
    private $revoked = false;

    /**
     * @var InitialAccessTokenId|null
     */
    private $initialAccessTokenId = null;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt = null;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId = null;

    /**
     * @return InitialAccessToken
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param UserAccountId           $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return InitialAccessToken
     */
    public function create(InitialAccessTokenId $initialAccessTokenId, UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt): self
    {
        $clone = clone $this;
        $clone->initialAccessTokenId = $initialAccessTokenId;
        $clone->expiresAt = $expiresAt;
        $clone->userAccountId = $userAccountId;

        $event = InitialAccessTokenEvent\InitialAccessTokenCreatedEvent::create($initialAccessTokenId, $userAccountId, $expiresAt);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return InitialAccessTokenId
     */
    public function getTokenId(): InitialAccessTokenId
    {
        if (null === $this->initialAccessTokenId) {
            throw new \LogicException('Initial Access Token not initialized.');
        }

        return $this->initialAccessTokenId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        if (null === $this->userAccountId) {
            throw new \LogicException('Initial Access Token not initialized.');
        }

        return $this->userAccountId;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ? \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < time();
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @return InitialAccessToken
     */
    public function markAsRevoked(): self
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = InitialAccessTokenEvent\InitialAccessTokenRevokedEvent::create($clone->getTokenId());
        $clone->record($event);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema';
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $initialAccessTokenId = InitialAccessTokenId::create($json->initial_access_token_id);
        $expiresAt = $json->expires_at ? \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at) : null;
        $userAccountId = $json->user_account_id ? UserAccountId::create($json->user_account_id) : null;
        $revoked = $json->is_revoked;

        $initialAccessToken = new self();
        $initialAccessToken->initialAccessTokenId = $initialAccessTokenId;
        $initialAccessToken->userAccountId = $userAccountId;
        $initialAccessToken->expiresAt = $expiresAt;
        $initialAccessToken->revoked = $revoked;

        return $initialAccessToken;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            '$schema' => $this->getSchema(),
            'type' => get_class($this),
            'initial_access_token_id' => $this->getTokenId() ? $this->getTokenId()->getValue() : null,
            'user_account_id' => $this->getUserAccountId() ? $this->getUserAccountId()->getValue() : null,
            'expires_at' => $this->getExpiresAt() ? $this->getExpiresAt()->getTimestamp() : null,
            'is_revoked' => $this->isRevoked(),
        ];

        return $data;
    }

    /**
     * @param Event $event
     *
     * @return InitialAccessToken
     */
    public function apply(Event $event): self
    {
        $map = $this->getEventMap();
        if (!array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if (null !== $this->initialAccessTokenId && $this->initialAccessTokenId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this initial access token.');
        }
        $method = $map[$event->getType()];

        return $this->$method($event);
    }

    /**
     * @return array
     */
    private function getEventMap(): array
    {
        return [
            InitialAccessTokenEvent\InitialAccessTokenCreatedEvent::class => 'applyInitialAccessTokenCreatedEvent',
            InitialAccessTokenEvent\InitialAccessTokenRevokedEvent::class => 'applyInitialAccessTokenRevokedEvent',
        ];
    }

    /**
     * @param InitialAccessTokenEvent\InitialAccessTokenCreatedEvent $event
     *
     * @return InitialAccessToken
     */
    protected function applyInitialAccessTokenCreatedEvent(InitialAccessTokenEvent\InitialAccessTokenCreatedEvent $event): self
    {
        $clone = clone $this;
        $clone->initialAccessTokenId = $event->getInitialAccessTokenId();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->userAccountId = $event->getUserAccountId();

        return $clone;
    }

    /**
     * @param InitialAccessTokenEvent\InitialAccessTokenRevokedEvent $event
     *
     * @return InitialAccessToken
     */
    protected function applyInitialAccessTokenRevokedEvent(InitialAccessTokenEvent\InitialAccessTokenRevokedEvent $event): self
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
