<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\Security\Authentication\Token;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class OAuth2Token extends AbstractToken
{
    /**
     * @var AccessToken
     */
    private $accessToken;

    public function __construct(AccessToken $accessToken)
    {
        parent::__construct();
        $this->accessToken = $accessToken;
    }

    public function getToken(): string
    {
        return $this->accessToken->getId()->getValue();
    }

    public function getTokenType(): string
    {
        return $this->accessToken->getParameter()->get('token_type');
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function getClientId(): string
    {
        return $this->accessToken->getClientId()->getValue();
    }

    public function getResourceOwnerId(): string
    {
        return $this->accessToken->getResourceOwnerId()->getValue();
    }

    public function getCredentials()
    {
        return $this->getToken();
    }
}
