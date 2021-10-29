<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class EmailScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'email';
    }

    public function getAssociatedClaims(): array
    {
        return ['email', 'email_verified'];
    }
}
