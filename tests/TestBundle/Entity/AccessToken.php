<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use DateTimeImmutable;
use OAuth2Framework\Component\Core\AccessToken\AbstractAccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

class AccessToken extends AbstractAccessToken
{
    public function __construct(
        private AccessTokenId $accessTokenId,
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
        AccessTokenId $accessTokenId,
        ClientId $clientId,
        ResourceOwnerId $resourceOwnerId,
        DateTimeImmutable $expiresAt,
        DataBag $parameter,
        DataBag $metadata,
        ?ResourceServerId $resourceServerId
    ): self {
        return new self(
            $accessTokenId,
            $clientId,
            $resourceOwnerId,
            $expiresAt,
            $parameter,
            $metadata,
            $resourceServerId
        );
    }

    public function getId(): AccessTokenId
    {
        return $this->accessTokenId;
    }
}
