<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\ScopeSupport;

class EmailScopeSupport implements UserInfoScopeSupport
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'email';
    }

    /**
     * {@inheritdoc}
     */
    public function parent(): ?string
    {
        return 'openid';
    }

    /**
     * {@inheritdoc}
     */
    public function isParentMandatory(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->name();
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociatedClaims(): array
    {
        return [
            'email',
            'email_verified',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->name();
    }
}
