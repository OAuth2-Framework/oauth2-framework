<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect;

use Jose\Component\KeyManagement\JKUFactory;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use OAuth2Framework\Component\OpenIdConnect\UserInfo\UserInfo;

class IdTokenBuilderFactory
{
    private ?JKUFactory $jkuFactory = null;

    private ?AuthorizationCodeRepository $authorizationCodeRepository = null;

    public function __construct(
        private string $issuer,
        private UserInfo $userinfo,
        private int $lifetime
    ) {
    }

    public function createBuilder(Client $client, UserAccount $userAccount, string $redirectUri): IdTokenBuilder
    {
        return new IdTokenBuilder(
            $this->issuer,
            $this->userinfo,
            $this->lifetime,
            $client,
            $userAccount,
            $redirectUri,
            $this->jkuFactory,
            $this->authorizationCodeRepository
        );
    }

    public function enableJkuSupport(JKUFactory $jkuFactory): self
    {
        $this->jkuFactory = $jkuFactory;

        return $this;
    }

    public function enableAuthorizationCodeSupport(AuthorizationCodeRepository $authorizationCodeRepository): self
    {
        $this->authorizationCodeRepository = $authorizationCodeRepository;

        return $this;
    }
}
