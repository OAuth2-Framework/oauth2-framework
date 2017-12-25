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

namespace OAuth2Framework\Component\Server\Core\AccessToken;

use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;

interface AccessTokenRepository
{
    /**
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param array                 $scopes
     * @param \DateTimeImmutable    $expiresAt
     * @param null|ResourceServerId $resourceServerId
     *
     * @return AccessToken
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId): AccessToken;

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
