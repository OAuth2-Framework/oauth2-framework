<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\RefreshTokenGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\AbstractRefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

final class RefreshToken extends AbstractRefreshToken
{
    public function __construct(
        private RefreshTokenId $id,
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ) {
        parent::__construct($clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }

    public static function create(
        RefreshTokenId $id,
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): self {
        return new self($id, $clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }

    public function getId(): RefreshTokenId
    {
        return $this->id;
    }
}
