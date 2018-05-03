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

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenIdGenerator as AccessTokenIdGeneratorInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

class AccessTokenIdGenerator implements AccessTokenIdGeneratorInterface
{
    /**
     * @var AccessTokenRepository
     */
    private $repository;

    /**
     * AccessTokenManager constructor.
     *
     * @param AccessTokenRepository $repository
     */
    public function __construct(AccessTokenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public function createAccessTokenId(ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, ? ResourceServerId $resourceServerId): AccessTokenId
    {
        $length = random_int(50, 100);

        return AccessTokenId::create(Base64Url::encode(random_bytes($length)));
    }

    /**
     * {@inheritdoc}
     */
    public function save(AccessToken $accessToken): void
    {
        // TODO: Implement save() method.
    }
}
