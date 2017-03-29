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

use OAuth2Framework\Component\Server\Model\InitialAccessToken\InitialAccessTokenId;

final class RevokeInitialAccessTokenCommand
{
    /**
     * @var InitialAccessTokenId
     */
    private $initialAccessTokenId;

    /**
     * RevokeInitialAccessTokenCommand constructor.
     *
     * @param InitialAccessTokenId $initialAccessTokenId
     */
    protected function __construct(InitialAccessTokenId $initialAccessTokenId)
    {
        $this->initialAccessTokenId = $initialAccessTokenId;
    }

    /**
     * @param InitialAccessTokenId $initialAccessTokenId
     *
     * @return RevokeInitialAccessTokenCommand
     */
    public static function create(InitialAccessTokenId $initialAccessTokenId): RevokeInitialAccessTokenCommand
    {
        return new self($initialAccessTokenId);
    }

    /**
     * @return InitialAccessTokenId
     */
    public function getInitialAccessTokenId(): InitialAccessTokenId
    {
        return $this->initialAccessTokenId;
    }
}
