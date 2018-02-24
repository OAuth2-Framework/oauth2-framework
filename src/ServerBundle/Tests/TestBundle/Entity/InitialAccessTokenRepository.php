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

class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var InitialAccessToken[]
     */
    private $initialAccessTokens = [];

    public function __construct()
    {
        $this->createInitialAccessTokens();
    }

    /**
     * {@inheritdoc}
     */
    public function find(InitialAccessTokenId $initialAccessTokenId): ? InitialAccessToken
    {
        return array_key_exists($initialAccessTokenId->getValue(), $this->initialAccessTokens) ? $this->initialAccessTokens[$initialAccessTokenId->getValue()] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(InitialAccessToken $initialAccessToken)
    {
        $this->initialAccessTokens[$initialAccessToken->getTokenId()->getValue()] = $initialAccessToken;
    }

    private function createInitialAccessTokens()
    {
        $iat = InitialAccessToken::createEmpty();
        $iat = $iat->create(
            InitialAccessTokenId::create('VALID_INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('john.1'),
            new \DateTimeImmutable('now +1 day')
        );
        $iat->eraseMessages();
        $this->save($iat);

        $iat = InitialAccessToken::createEmpty();
        $iat = $iat->create(
            InitialAccessTokenId::create('EXPIRED_INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('john.1'),
            new \DateTimeImmutable('now -1 day')
        );
        $iat->eraseMessages();
        $this->save($iat);
    }
}
