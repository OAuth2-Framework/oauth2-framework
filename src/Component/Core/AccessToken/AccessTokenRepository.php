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

interface AccessTokenRepository
{
    /**
     * @param AccessToken $token
     */
    public function save(AccessToken $token);

    /**
     * @param AccessTokenId $accessTokenId The access token ID
     *
     * @return AccessToken|null Return the access token or null if the argument is not a valid access token
     */
    public function find(AccessTokenId $accessTokenId);
}
