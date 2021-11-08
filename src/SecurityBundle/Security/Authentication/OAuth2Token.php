<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class OAuth2Token extends AbstractToken
{
    public function __construct(
        private AccessToken $accessToken
    ) {
        parent::__construct();
    }

    public function getToken(): string
    {
        return $this->accessToken->getId()
            ->getValue()
        ;
    }

    public function getTokenType(): string
    {
        return $this->accessToken->getParameter()
            ->get('token_type')
        ;
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function getClientId(): string
    {
        return $this->accessToken->getClientId()
            ->getValue()
        ;
    }

    public function getResourceOwnerId(): string
    {
        return $this->accessToken->getResourceOwnerId()
            ->getValue()
        ;
    }

    public function getCredentials(): string
    {
        return $this->getToken();
    }
}
