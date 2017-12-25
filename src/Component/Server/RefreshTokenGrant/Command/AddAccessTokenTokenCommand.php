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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant\Command;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenId;

final class AddAccessTokenTokenCommand
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * AddAccessTokenTokenCommand constructor.
     *
     * @param RefreshTokenId $refreshTokenId
     * @param AccessTokenId $accessTokenId
     */
    protected function __construct(RefreshTokenId $refreshTokenId, AccessTokenId $accessTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
        $this->accessTokenId = $accessTokenId;
    }

    /**
     * @param RefreshTokenId $refreshTokenId
     *
     * @return AddAccessTokenTokenCommand
     */
    public static function create(RefreshTokenId $refreshTokenId, AccessTokenId $accessTokenId): AddAccessTokenTokenCommand
    {
        return new self($refreshTokenId, $accessTokenId);
    }

    /**
     * @return RefreshTokenId
     */
    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }
}
