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

namespace OAuth2Framework\Component\Core\AccessToken;

class AccessTokenHandlerManager
{
    /**
     * @var AccessTokenHandler[]
     */
    private $accessTokenHandlers = [];

    /**
     * @param AccessTokenHandler $accessTokenHandler
     */
    public function add(AccessTokenHandler $accessTokenHandler): void
    {
        $this->accessTokenHandlers[] = $accessTokenHandler;
    }

    /**
     * @param AccessTokenId $tokenId
     *
     * @return null|AccessToken
     */
    public function find(AccessTokenId $tokenId): ?AccessToken
    {
        foreach ($this->accessTokenHandlers as $accessTokenHandler) {
            $accessToken = $accessTokenHandler->find($tokenId);
            if (null !== $accessToken) {
                return $accessToken;
            }
        }

        return null;
    }
}
