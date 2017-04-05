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

namespace OAuth2Framework\Component\Server\Event\InitialAccessToken;

use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class InitialAccessTokenCreatedEvent extends Event
{
    /**
     * @var InitialAccessTokenId
     */
    private $initialAccessTokenId;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId;

    /**
     * InitialAccessTokenCreatedEvent constructor.
     *
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param null|\DateTimeImmutable $expiresAt
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(InitialAccessTokenId $initialAccessTokenId, ? UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->initialAccessTokenId = $initialAccessTokenId;
        $this->expiresAt = $expiresAt;
        $this->userAccountId = $userAccountId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema';
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param null|\DateTimeImmutable $expiresAt
     * @param UserAccountId|null      $userAccountId
     *
     * @return InitialAccessTokenCreatedEvent
     */
    public static function create(InitialAccessTokenId $initialAccessTokenId, ? UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt): InitialAccessTokenCreatedEvent
    {
        return new self($initialAccessTokenId, $userAccountId, $expiresAt, null, null);
    }

    /**
     * @return InitialAccessTokenId
     */
    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * @return null|UserAccountId
     */
    public function getUserAccountId()
    {
        return $this->userAccountId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getInitialAccessTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'user_account_id' => $this->userAccountId ? $this->userAccountId->getValue() : null,
            'expires_at' => $this->expiresAt ? $this->expiresAt->getTimestamp() : null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $initialAccessTokenId = InitialAccessTokenId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        $userAccountId = null === $json->payload->user_account_id ? null : UserAccountId::create($json->payload->user_account_id);
        $expiresAt = null === $json->payload->expires_at ? null : \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);

        return new self(
            $initialAccessTokenId,
            $userAccountId,
            $expiresAt,
            $recordedOn,
            $eventId
        );
    }
}
