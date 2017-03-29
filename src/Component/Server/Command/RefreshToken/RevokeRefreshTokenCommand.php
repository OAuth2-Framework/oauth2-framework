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

namespace OAuth2Framework\Component\Server\Command\RefreshToken;

use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;

final class RevokeRefreshTokenCommand
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

    /**
     * RevokeRefreshTokenIdCommand constructor.
     *
     * @param RefreshTokenId $refreshTokenId
     */
    protected function __construct(RefreshTokenId $refreshTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
    }

    /**
     * @param RefreshTokenId $refreshTokenId
     *
     * @return RevokeRefreshTokenCommand
     */
    public static function create(RefreshTokenId $refreshTokenId): RevokeRefreshTokenCommand
    {
        return new self($refreshTokenId);
    }

    /**
     * @return RefreshTokenId
     */
    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }
}
