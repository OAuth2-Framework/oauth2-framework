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
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class InitialAccessTokenCreatedEvent extends Event
{
    /**
     * @var InitialAccessTokenId
     */
    private $initialAccessTokenId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * InitialAccessTokenCreatedEvent constructor.
     *
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param UserAccountId           $userAccountId
     * @param null|\DateTimeImmutable $expiresAt
     */
    protected function __construct(InitialAccessTokenId $initialAccessTokenId, UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt)
    {
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
     * @param UserAccountId           $userAccountId
     * @param null|\DateTimeImmutable $expiresAt
     *
     * @return InitialAccessTokenCreatedEvent
     */
    public static function create(InitialAccessTokenId $initialAccessTokenId, UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt): self
    {
        return new self($initialAccessTokenId, $userAccountId, $expiresAt);
    }

    /**
     * @return InitialAccessTokenId
     */
    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
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
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $initialAccessTokenId = InitialAccessTokenId::create($json->domain_id);
        $userAccountId = null === $json->payload->user_account_id ? null : UserAccountId::create($json->payload->user_account_id);
        $expiresAt = null === $json->payload->expires_at ? null : \DateTimeImmutable::createFromFormat('U', (string) $json->payload->expires_at);

        return new self(
            $initialAccessTokenId,
            $userAccountId,
            $expiresAt
        );
    }
}
