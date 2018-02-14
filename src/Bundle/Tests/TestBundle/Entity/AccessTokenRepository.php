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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use Base64Url\Base64Url;
use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository as AccessTokenRepositoryInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @var AccessToken[]
     */
    private $accessTokens = [];

    /**
     * {@inheritdoc}
     */
    public function find(AccessTokenId $accessTokenId)
    {
        return array_key_exists($accessTokenId->getValue(), $this->accessTokens) ? $this->accessTokens[$accessTokenId->getValue()] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(AccessToken $accessToken)
    {
        $this->accessTokens[$accessToken->getTokenId()->getValue()] = $accessToken;
    }
}
