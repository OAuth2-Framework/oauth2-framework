<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use DateTimeImmutable;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken as InitialAccessTokenInterface;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository as InitialAccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\InitialAccessToken;

final class InitialAccessTokenRepository implements InitialAccessTokenRepositoryInterface
{
    /**
     * @var array<string, InitialAccessTokenInterface>
     */
    private array $initialAccessTokens = [];

    public function __construct()
    {
        $this->createInitialAccessTokens();
    }

    public function find(InitialAccessTokenId $initialAccessTokenId): ?InitialAccessTokenInterface
    {
        return $this->initialAccessTokens[$initialAccessTokenId->getValue()] ?? null;
    }

    public function save(InitialAccessTokenInterface $initialAccessToken): void
    {
        $this->initialAccessTokens[$initialAccessToken->getId()->getValue()] = $initialAccessToken;
    }

    private function createInitialAccessTokens(): void
    {
        $this->save(
            InitialAccessToken::create(
                InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now +1 day')
            )
        );
        $this->save(
            InitialAccessToken::create(
                InitialAccessTokenId::create('REVOKED_INITIAL_ACCESS_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now +1 day')
            )->markAsRevoked()
        );
        $this->save(
            InitialAccessToken::create(
                InitialAccessTokenId::create('EXPIRED_INITIAL_ACCESS_TOKEN_ID'),
                UserAccountId::create('USER_ACCOUNT_ID'),
                new DateTimeImmutable('now -1 day')
            )
        );
    }
}
