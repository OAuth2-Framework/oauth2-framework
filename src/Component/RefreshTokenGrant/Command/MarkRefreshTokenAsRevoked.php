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

namespace OAuth2Framework\Component\RefreshTokenGrant\Command;

use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

class MarkRefreshTokenAsRevoked
{
    private $refreshTokenId;

    public function __construct(RefreshTokenId $refreshTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
    }

    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }
}
