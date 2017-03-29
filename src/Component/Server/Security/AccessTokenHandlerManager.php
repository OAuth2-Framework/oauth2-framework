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

namespace OAuth2Framework\Component\Server\Security;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;

final class AccessTokenHandlerManager
{
    /**
     * @var AccessTokenHandlerInterface[]
     */
    private $accessTokenHandlers = [];

    /**
     * @param AccessTokenHandlerInterface $accessTokenHandler
     *
     * @return AccessTokenHandlerManager
     */
    public function add(AccessTokenHandlerInterface $accessTokenHandler): AccessTokenHandlerManager
    {
        $this->accessTokenHandlers[] = $accessTokenHandler;

        return $this;
    }

    /**
     * @param string $token
     *
     * @return null|AccessToken
     */
    public function find(string $token)
    {
        foreach ($this->accessTokenHandlers as $accessTokenHandler) {
            $accessToken = $accessTokenHandler->find($token);
            if (null !== $accessToken) {
                return $accessToken;
            }
        }
    }
}
