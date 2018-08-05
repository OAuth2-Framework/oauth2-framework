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

namespace OAuth2Framework\Component\RefreshTokenGrant\Event;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

class AccessTokenAddedToRefreshTokenEvent extends Event
{
    private $refreshTokenId;
    private $accessTokenId;

    public function __construct(RefreshTokenId $refreshTokenId, AccessTokenId $accessTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
        $this->accessTokenId = $accessTokenId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema';
    }

    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    public function getDomainId(): Id
    {
        return $this->getRefreshTokenId();
    }

    public function getPayload()
    {
        return [
            'access_token_id' => $this->accessTokenId->getValue(),
        ];
    }
}
