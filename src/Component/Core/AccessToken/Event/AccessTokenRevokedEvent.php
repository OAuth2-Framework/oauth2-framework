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

namespace OAuth2Framework\Component\Core\AccessToken\Event;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class AccessTokenRevokedEvent extends Event
{
    private $accessTokenId;

    public function __construct(AccessTokenId $accessTokenId)
    {
        $this->accessTokenId = $accessTokenId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/revoked/1.0/schema';
    }

    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    public function getDomainId(): Id
    {
        return $this->getAccessTokenId();
    }

    public function getPayload()
    {
    }
}
