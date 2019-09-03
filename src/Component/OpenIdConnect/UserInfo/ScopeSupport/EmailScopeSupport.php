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

class EmailScopeSupport implements UserInfoScopeSupport
{
    public function getName(): string
    {
        return 'email';
    }

    public function getAssociatedClaims(): array
    {
        return [
            'email',
            'email_verified',
        ];
    }
}
