<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class PhoneScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'phone';
    }

    public function getAssociatedClaims(): array
    {
        return ['phone_number', 'phone_number_verified'];
    }
}
