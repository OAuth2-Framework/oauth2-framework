<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\AbstractRefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

class RefreshToken extends AbstractRefreshToken
{
    public function __construct(
        private RefreshTokenId $refreshTokenId,
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ) {
        parent::__construct($clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }

    public function getId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }
}
