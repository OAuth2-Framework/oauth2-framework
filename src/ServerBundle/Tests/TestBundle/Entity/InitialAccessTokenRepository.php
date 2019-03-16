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

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var InitialAccessToken[]
     */
    private $initialAccessTokens = [];

    public function __construct()
    {
        $this->createInitialAccessTokens();
    }

    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessToken
    {
        return \array_key_exists($initialAccessTokenId->getValue(), $this->initialAccessTokens) ? $this->initialAccessTokens[$initialAccessTokenId->getValue()] : null;
    }

    public function save(InitialAccessToken $initialAccessToken)
    {
        $this->initialAccessTokens[$initialAccessToken->getId()->getValue()] = $initialAccessToken;
    }

    private function createInitialAccessTokens()
    {
        $iat = new InitialAccessToken(
            new InitialAccessTokenId('VALID_INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('john.1'),
            new \DateTimeImmutable('now +1 day')
        );
        $this->save($iat);

        $iat = new InitialAccessToken(
            new InitialAccessTokenId('EXPIRED_INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('john.1'),
            new \DateTimeImmutable('now -1 day')
        );
        $this->save($iat);
    }
}
