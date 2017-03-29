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

namespace OAuth2Framework\Component\Server\Command\InitialAccessToken;

use OAuth2Framework\Component\Server\Command\CommandWithDataTransporter;
use OAuth2Framework\Component\Server\DataTransporter;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class CreateInitialAccessTokenCommand extends CommandWithDataTransporter
{
    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $expiresAt;

    /**
     * CreateInitialAccessTokenCommand constructor.
     *
     * @param UserAccountId           $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataTransporter|null    $dataTransporter
     */
    protected function __construct(UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt, ?DataTransporter $dataTransporter)
    {
        $this->userAccountId = $userAccountId;
        $this->expiresAt = $expiresAt;
        parent::__construct($dataTransporter);
    }

    /**
     * @param UserAccountId           $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     * @param DataTransporter|null    $dataTransporter
     *
     * @return CreateInitialAccessTokenCommand
     */
    public static function create(UserAccountId $userAccountId, ?\DateTimeImmutable $expiresAt, ?DataTransporter $dataTransporter): CreateInitialAccessTokenCommand
    {
        return new self($userAccountId, $expiresAt, $dataTransporter);
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return null|\DateTimeImmutable
     */
    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }
}
