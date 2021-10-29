<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use Assert\Assertion;
use DateTimeImmutable;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken as InitialAccessTokenInterface;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository as InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\InitialAccessToken;
use Psr\Cache\CacheItemPoolInterface;

final class InitialAccessTokenRepository implements InitialAccessTokenRepositoryInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache
    ) {
        $this->createInitialAccessTokens();
    }

    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessTokenInterface
    {
        $item = $this->cache->getItem('InitialAccessToken-' . $initialAccessTokenId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(InitialAccessTokenInterface $initialAccessToken): void
    {
        Assertion::isInstanceOf($initialAccessToken, InitialAccessToken::class, 'Unsupported entity');
        $item = $this->cache->getItem('InitialAccessToken-' . $initialAccessToken->getId()->getValue());
        $item->set($initialAccessToken);
        $this->cache->save($item);
    }

    private function createInitialAccessTokens(): void
    {
        $iat = new InitialAccessToken(
            new InitialAccessTokenId('VALID_INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('john.1'),
            new DateTimeImmutable('now +1 day')
        );
        $this->save($iat);

        $iat = new InitialAccessToken(
            new InitialAccessTokenId('EXPIRED_INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('john.1'),
            new DateTimeImmutable('now -1 day')
        );
        $this->save($iat);

        $iat = new InitialAccessToken(
            new InitialAccessTokenId('REVOKED_INITIAL_ACCESS_TOKEN_ID'),
            new UserAccountId('john.1'),
            new DateTimeImmutable('now +1 day')
        );
        $iat = $iat->markAsRevoked();
        $this->save($iat);
    }
}
