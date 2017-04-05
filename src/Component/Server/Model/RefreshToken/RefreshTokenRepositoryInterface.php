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

namespace OAuth2Framework\Component\Server\Model\RefreshToken;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;

/**
 * @see    http://tools.ietf.org/html/rfc6749#section-6
 * @see    http://tools.ietf.org/html/rfc6749#section-1.5
 */
interface RefreshTokenRepositoryInterface
{
    /**
     * @param ResourceOwnerId         $resourceOwnerId
     * @param ClientId                $clientId
     * @param DataBag                 $parameters
     * @param DataBag                 $metadatas
     * @param string[]                $scopes
     * @param ResourceServerId|null   $resourceServerId
     * @param \DateTimeImmutable|null $expiresAt
     *
     * @return RefreshToken
     */
    public function create(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $expiresAt): RefreshToken;

    /**
     * @param RefreshToken $refreshToken
     */
    public function save(RefreshToken $refreshToken);

    /**
     * @param RefreshTokenId $refreshTokenId refresh token ID
     *
     * @return RefreshToken|null
     *
     * @see     http://tools.ietf.org/html/rfc6749#section-6
     */
    public function find(RefreshTokenId $refreshTokenId);
}
