<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\AccessToken;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;

interface AccessTokenRepositoryInterface
{
    /**
     * @param ResourceOwnerId         $resourceOwnerId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param array                   $scopes
     * @param null|RefreshTokenId     $refreshTokenId
     * @param null|ResourceServerId   $resourceServerId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return AccessToken
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ?RefreshTokenId $refreshTokenId, ?ResourceServerId $resourceServerId, ?\DateTimeImmutable $expiresAt): AccessToken;

    /**
     * @param AccessToken $token
     */
    public function save(AccessToken $token);

    /**
     * @param AccessTokenId $accessTokenId The access token ID
     *
     * @return AccessToken|null Return the access token or null if the argument is not a valid access token
     */
    public function find(AccessTokenId $accessTokenId);
}
