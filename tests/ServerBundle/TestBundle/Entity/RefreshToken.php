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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\RefreshTokenGrant\AbstractRefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;

class RefreshToken extends AbstractRefreshToken
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

    public function __construct(RefreshTokenId $refreshTokenId, ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        parent::__construct($clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
        $this->refreshTokenId = $refreshTokenId;
    }

    public function getId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }
}
