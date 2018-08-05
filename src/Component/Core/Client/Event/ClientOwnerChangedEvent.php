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

namespace OAuth2Framework\Component\Core\Client\Event;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class ClientOwnerChangedEvent extends Event
{
    private $clientId;
    private $newOwnerId;

    public function __construct(ClientId $clientId, UserAccountId $newOwnerId)
    {
        $this->clientId = $clientId;
        $this->newOwnerId = $newOwnerId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema';
    }

    public function getDomainId(): Id
    {
        return $this->getClientId();
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getNewOwnerId(): UserAccountId
    {
        return $this->newOwnerId;
    }

    public function getPayload()
    {
        return [
            'new_owner_id' => $this->newOwnerId->getValue(),
        ];
    }
}
