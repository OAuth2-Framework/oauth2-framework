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

namespace OAuth2Framework\Component\OpenIdConnect\UserInfo\Claim;

use OAuth2Framework\Component\AuthorizationEndpoint\User\AuthenticationMethodReferenceSupport;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class AuthenticationMethodReference implements Claim
{
    private const CLAIM_NAME = 'amr';

    /**
     * @var AuthenticationMethodReferenceSupport
     */
    private $authenticationMethodReferenceSupport;

    public function __construct(AuthenticationMethodReferenceSupport $authenticationMethodReferenceSupport)
    {
        $this->authenticationMethodReferenceSupport = $authenticationMethodReferenceSupport;
    }

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return null !== $this->authenticationMethodReferenceSupport->getAuthenticationMethodReferenceFor($userAccount);
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $this->authenticationMethodReferenceSupport->getAuthenticationMethodReferenceFor($userAccount);
    }
}
