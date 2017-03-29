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

namespace OAuth2Framework\Component\Server\Command\Client;

use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

final class ChangeOwnerCommand
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * ChangeOwnerCommand constructor.
     *
     * @param Client        $client
     * @param UserAccountId $userAccountId
     */
    protected function __construct(Client $client, UserAccountId $userAccountId)
    {
        $this->client = $client;
        $this->userAccountId = $userAccountId;
    }

    /**
     * @param Client        $client
     * @param UserAccountId $userAccountId
     *
     * @return ChangeOwnerCommand
     */
    public static function create(Client $client, UserAccountId $userAccountId): ChangeOwnerCommand
    {
        return new self($client, $userAccountId);
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @return UserAccountId
     */
    public function getNewOwnerId(): UserAccountId
    {
        return $this->userAccountId;
    }
}
