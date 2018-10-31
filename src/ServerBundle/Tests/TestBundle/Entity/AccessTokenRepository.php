<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\AccessToken\AccessToken as CoreAccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository as AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

final class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AccessToken[]
     */
    private $accessTokens = [];

    public function save(CoreAccessToken $accessToken): void
    {
        $this->accessTokens[$accessToken->getTokenId()->getValue()] = $accessToken;
    }

    public function find(AccessTokenId $accessTokenId): ?CoreAccessToken
    {
        return \array_key_exists($accessTokenId->getValue(), $this->accessTokens) ? $this->accessTokens[$accessTokenId->getValue()] : null;
    }

    public function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): CoreAccessToken
    {
        return new AccessToken(new AccessTokenId(\bin2hex(\random_bytes(32))), $clientId, $resourceOwnerId, $expiresAt, $parameter, $metadata, $resourceServerId);
    }
}
