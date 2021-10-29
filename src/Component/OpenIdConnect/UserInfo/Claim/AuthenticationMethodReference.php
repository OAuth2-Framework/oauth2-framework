<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\AuthorizationEndpoint\User\AuthenticationMethodReferenceSupport;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class AuthenticationMethodReference implements Claim
{
    private const CLAIM_NAME = 'amr';

    public function __construct(
        private AuthenticationMethodReferenceSupport $authenticationMethodReferenceSupport
    ) {
    }

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return $this->authenticationMethodReferenceSupport->getAuthenticationMethodReferenceFor($userAccount) !== null;
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $this->authenticationMethodReferenceSupport->getAuthenticationMethodReferenceFor($userAccount);
    }
}
