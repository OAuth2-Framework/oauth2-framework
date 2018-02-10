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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;

class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var InitialAccessToken[]
     */
    private $initialAccessTokens = [];

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
}
