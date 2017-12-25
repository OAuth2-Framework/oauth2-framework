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

namespace OAuth2Framework\Component\Server\Core\Client\Command;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;

final class ChangeOwnerCommand
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * ChangeOwnerCommand constructor.
     *
     * @param ClientId      $clientId
     * @param UserAccountId $userAccountId
     */
    protected function __construct(ClientId $clientId, UserAccountId $userAccountId)
    {
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
    }

    /**
     * @param ClientId      $clientId
     * @param UserAccountId $userAccountId
     *
     * @return ChangeOwnerCommand
     */
    public static function create(ClientId $clientId, UserAccountId $userAccountId): ChangeOwnerCommand
    {
        return new self($clientId, $userAccountId);
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return UserAccountId
     */
    public function getNewOwnerId(): UserAccountId
    {
        return $this->userAccountId;
    }
}
