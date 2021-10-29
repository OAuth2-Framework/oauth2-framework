<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class AddressScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'address';
    }

    public function getAssociatedClaims(): array
    {
        return ['address'];
    }
}
