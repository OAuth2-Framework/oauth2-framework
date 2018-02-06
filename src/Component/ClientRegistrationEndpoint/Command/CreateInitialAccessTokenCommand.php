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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Command;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class CreateInitialAccessTokenCommand
{
    /**
     * @var InitialAccessTokenId
     */
    private $initialAccessTokenId;

    /**
     * @var null|UserAccountId
     */
    private $userAccountId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * CreateInitialAccessTokenCommand constructor.
     *
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param null|UserAccountId      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     */
    protected function __construct(InitialAccessTokenId $initialAccessTokenId, ?UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt)
    {
        $this->initialAccessTokenId = $initialAccessTokenId;
        $this->userAccountId = $userAccountId;
        $this->expiresAt = $expiresAt;
    }

    /**
     * @param InitialAccessTokenId    $initialAccessTokenId
     * @param null|UserAccountId      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return CreateInitialAccessTokenCommand
     */
    public static function create(InitialAccessTokenId $initialAccessTokenId, ?UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt): self
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
     * @return null|UserAccountId
     */
    public function getUserAccountId(): ?UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return null|\DateTimeImmutable
     */
    public function getExpiresAt(): ? \DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
