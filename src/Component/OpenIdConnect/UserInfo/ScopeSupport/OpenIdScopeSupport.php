<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class OpenIdScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'openid';
    }

    public function getAssociatedClaims(): array
    {
        return [
            'sub',
        ];
    }
}
