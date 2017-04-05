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

namespace OAuth2Framework\Bundle\Server\Security\Authentication\Token;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

final class OAuth2Token extends AbstractToken
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var AccessToken
     */
    private $accessToken;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ResourceOwnerInterface
     */
    private $resource_owner;

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param AccessToken $accessToken
     */
    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param ResourceOwnerInterface $resource_owner
     */
    public function setResourceOwner(ResourceOwnerInterface $resource_owner)
    {
        $this->resource_owner = $resource_owner;
    }

    /**
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner()
    {
        return $this->resource_owner;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->token;
    }
}
