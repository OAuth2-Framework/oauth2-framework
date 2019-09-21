<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Repository;

use Assert\Assertion;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken as InitialAccessTokenInterface;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\InitialAccessToken;
use Psr\Cache\CacheItemPoolInterface;

final class InitialAccessTokenRepository implements \OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        $this->createInitialAccessTokens();
    }

    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessTokenInterface
    {
        $item = $this->cache->getItem('InitialAccessToken-'.$initialAccessTokenId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(InitialAccessTokenInterface $initialAccessToken): void
    {
        Assertion::isInstanceOf($initialAccessToken, InitialAccessToken::class, 'Unsupported entity');
        $item = $this->cache->getItem('InitialAccessToken-'.$initialAccessToken->getId()->getValue());
        $item->set($initialAccessToken);
        $this->cache->save($item);
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
