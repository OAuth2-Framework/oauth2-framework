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

namespace OAuth2Framework\Component\Server\Model\InitialAccessToken;

use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;

interface InitialAccessTokenRepositoryInterface
{
    /**
     * @param UserAccountId|null      $userAccountId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return InitialAccessToken
     */
    public function create(? UserAccountId $userAccountId, ? \DateTimeImmutable $expiresAt): InitialAccessToken;

    /**
     * @param InitialAccessToken $initialAccessToken
     */
    public function save(InitialAccessToken $initialAccessToken);

    /**
     * This function verifies the request and validate or not the initial access token.
     * MUST return null if the initial access token is not valid (expired, revoked...).
     *
     * @param InitialAccessTokenId $initialAccessTokenId The initial access token
     *
     * @return InitialAccessToken|null Return the initial access token or null if the argument is not a valid initial access token
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ? InitialAccessToken;
}
