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

namespace OAuth2Framework\SecurityBundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;

final class AccessTokenHandler implements \OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler
{
    /**
     * @var AccessToken[]
     */
    private $accessTokens = [];

    /**
     * @param AccessTokenId $tokenId
     *
     * @return null|AccessToken
     */
    public function find(AccessTokenId $tokenId): ?AccessToken
    {
        return array_key_exists($tokenId->getValue(), $this->accessTokens) ? $this->accessTokens[$tokenId->getValue()] : null;
    }

    /**
     * @param AccessToken $token
     */
    public function save(AccessToken $token): void
    {
        $this->accessTokens[$token->getAccessTokenId()->getValue()] = $token;
    }
}