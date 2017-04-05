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

namespace OAuth2Framework\Component\Server\Command\AccessToken;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;

final class RevokeAccessTokenCommand
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * RevokeAccessTokenIdCommand constructor.
     *
     * @param AccessTokenId $accessTokenId
     */
    private function __construct(AccessTokenId $accessTokenId)
    {
        $this->accessTokenId = $accessTokenId;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return RevokeAccessTokenCommand
     */
    public static function create(AccessTokenId $accessTokenId): RevokeAccessTokenCommand
    {
        return new self($accessTokenId);
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }
}
