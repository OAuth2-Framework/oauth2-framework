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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository as AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AccessToken[]
     */
    private $accessTokens = [];

    public function save(AccessToken $accessToken): void
    {
        $this->accessTokens[$accessToken->getTokenId()->getValue()] = $accessToken;
    }

    public function find(AccessTokenId $accessTokenId): ?AccessToken
    {
        return \array_key_exists($accessTokenId->getValue(), $this->accessTokens) ? $this->accessTokens[$accessTokenId->getValue()] : null;
    }
}
