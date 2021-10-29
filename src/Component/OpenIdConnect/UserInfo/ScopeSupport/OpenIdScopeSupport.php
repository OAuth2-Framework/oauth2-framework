<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class OpenIdScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'openid';
    }

    public function getAssociatedClaims(): array
    {
        return ['sub'];
    }
}
