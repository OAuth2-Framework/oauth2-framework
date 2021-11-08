<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AccessToken as AccessTokenInterface;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository as AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Tests\TestBundle\Entity\AccessToken;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var array<string, AccessTokenInterface>
     */
    private array $tokens = [];

    public function find(AccessTokenId $accessTokenId): ?AccessTokenInterface
    {
        return $this->tokens[$accessTokenId->getValue()] ?? null;
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        $this->tokens[$accessToken->getId()->getValue()] = $accessToken;
    }

    public function create(
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): AccessTokenInterface {
        return new AccessToken(AccessTokenId::create(bin2hex(
            random_bytes(32)
        )), $clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }
}
