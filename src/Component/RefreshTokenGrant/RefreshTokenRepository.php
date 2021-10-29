<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

/**
 * @see    http://tools.ietf.org/html/rfc6749#section-6
 * @see    http://tools.ietf.org/html/rfc6749#section-1.5
 */
interface RefreshTokenRepository
{
    public function save(RefreshToken $refreshToken): void;

    public function find(RefreshTokenId $refreshTokenId): ?RefreshToken;

    public function create(
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): RefreshToken;
}
