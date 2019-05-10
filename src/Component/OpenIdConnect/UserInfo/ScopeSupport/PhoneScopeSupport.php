<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class PhoneScopeSupport implements UserInfoScopeSupport
{
    public function name(): string
    {
        return 'phone';
    }

    public function parent(): ?string
    {
        return 'openid';
    }

    public function isParentMandatory(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function getAssociatedClaims(): array
    {
        return [
            'phone_number',
            'phone_number_verified',
        ];
    }

    public function jsonSerialize(): string
    {
        return $this->name();
    }
}
