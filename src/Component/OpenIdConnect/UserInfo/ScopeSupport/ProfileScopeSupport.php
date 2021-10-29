<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class ProfileScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'profile';
    }

    public function getAssociatedClaims(): array
    {
        return [
            'name',
            'given_name',
            'middle_name',
            'family_name',
            'nickname',
            'preferred_username',
            'profile',
            'picture',
            'website',
            'gender',
            'birthdate',
            'zoneinfo',
            'locale',
            'updated_at',
        ];
    }
}
