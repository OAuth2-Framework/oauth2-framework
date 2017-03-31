<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\InitialAccessToken;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Event\InitialAccessToken as InitialAccessTokenEvent;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

final class InitialAccessToken implements ContainsRecordedMessages, DomainObjectInterface
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
    public static function createEmpty(): InitialAccessToken
    {
        return new self();
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return InitialAccessToken
     */
    public function create(InitialAccessTokenId $initialAccessTokenId, ?UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt): InitialAccessToken
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
    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        Assertion::notNull($this->initialAccessTokenId, 'Initial Access Token not initialized.');

        return $this->initialAccessTokenId;
    }

    /**
     * @return UserAccountId|null
     */
    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getExpiresAt(): ?\DateTimeImmutable
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
    public function markAsRevoked(): InitialAccessToken
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = InitialAccessTokenEvent\InitialAccessTokenRevokedEvent::create($clone->getInitialAccessTokenId());
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
    public static function createFromJson(\stdClass $json): DomainObjectInterface
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
            'initial_access_token_id' => $this->getInitialAccessTokenId() ? $this->getInitialAccessTokenId()->getValue() : null,
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
    public function apply(Event $event): InitialAccessToken
    {
        $map = $this->getEventMap();
        Assertion::keyExists($map, $event->getType(), 'Unsupported event.');
        if (null !== $this->initialAccessTokenId) {
            Assertion::eq($this->initialAccessTokenId, $event->getDomainId(), 'Event not applicable for this initial access token.');
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
    protected function applyInitialAccessTokenCreatedEvent(InitialAccessTokenEvent\InitialAccessTokenCreatedEvent $event): InitialAccessToken
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
    protected function applyInitialAccessTokenRevokedEvent(InitialAccessTokenEvent\InitialAccessTokenRevokedEvent $event): InitialAccessToken
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
