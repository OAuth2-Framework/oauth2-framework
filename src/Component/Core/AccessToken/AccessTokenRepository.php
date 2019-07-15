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

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

interface AccessTokenRepository
{
    /**
     * @param AccessTokenId $accessTokenId The access token ID
     *
     * @return null|AccessToken Return the access token or null if the argument is not a valid access token
     */
    public function find(AccessTokenId $accessTokenId): ?AccessToken;

    /**
     * @param AccessToken $accessToken The access token to store
     */
    public function save(AccessToken $accessToken): void;

    /**
     * @return AccessToken This method creates an access token
     */
    public function create(ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId): AccessToken;
}
