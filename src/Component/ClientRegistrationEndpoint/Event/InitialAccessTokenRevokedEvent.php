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
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class InitialAccessTokenRevokedEvent extends Event
{
    private $initialAccessTokenId;

    public function __construct(InitialAccessTokenId $initialAccessTokenId)
    {
        $this->initialAccessTokenId = $initialAccessTokenId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema';
    }

    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }

    public function getDomainId(): Id
    {
        return $this->getInitialAccessTokenId();
    }

    public function getPayload()
    {
    }
}
