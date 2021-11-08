<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken as RefreshTokenInterface;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository as RefreshTokenRepositoryInterface;
use OAuth2Framework\Tests\TestBundle\Entity\RefreshToken;

final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @var array<string, RefreshTokenInterface>
     */
    private array $refreshTokens = [];

    public function __construct()
    {
        $this->load();
    }

    public function find(RefreshTokenId $refreshTokenId): ?RefreshTokenInterface
    {
        return $this->refreshTokens[$refreshTokenId->getValue()] ?? null;
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->refreshTokens[$refreshToken->getId()->getValue()] = $refreshToken;
    }

    public function create(
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): RefreshTokenInterface {
        return new RefreshToken(
            RefreshTokenId::create(bin2hex(random_bytes(32))),
            $clientId,
            $resourceOwnerId,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }

    private function load(): void
    {
        foreach ($this->getData() as $datum) {
            $refreshToken = new RefreshToken(
                RefreshTokenId::create($datum['refresh_token_id']),
                new ClientId($datum['client_id']),
                $datum['resource_owner_id'],
                $datum['expires_at'],
                new DataBag($datum['parameter']),
                new DataBag($datum['metadata']),
                $datum['resource_server_id']
            );
            if ($datum['is_revoked']) {
                $refreshToken->markAsRevoked();
            }
            $this->save($refreshToken);
        }
    }

    private function getData(): array
    {
        return [
            [
                'refresh_token_id' => 'VALID_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
            [
                'refresh_token_id' => 'REVOKED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new DateTimeImmutable('now +1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => true,
            ],
            [
                'refresh_token_id' => 'EXPIRED_REFRESH_TOKEN',
                'client_id' => 'CLIENT_ID_3',
                'resource_owner_id' => new UserAccountId('john.1'),
                'expires_at' => new DateTimeImmutable('now -1 day'),
                'parameter' => [],
                'metadata' => [],
                'resource_server_id' => null,
                'is_revoked' => false,
            ],
        ];
    }
}
