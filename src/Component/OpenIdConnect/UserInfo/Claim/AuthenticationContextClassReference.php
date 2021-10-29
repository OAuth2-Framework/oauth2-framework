<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\AuthorizationEndpoint\User\AuthenticationContextClassReferenceSupport;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class AuthenticationContextClassReference implements Claim
{
    private const CLAIM_NAME = 'acr';

    public function __construct(
        private AuthenticationContextClassReferenceSupport $authenticationContextClassReferenceSupport
    ) {
    }

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return $this->authenticationContextClassReferenceSupport->getAuthenticationContextClassReferenceFor(
            $userAccount
        ) !== null;
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $this->authenticationContextClassReferenceSupport->getAuthenticationContextClassReferenceFor(
            $userAccount
        );
    }
}
