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

use OAuth2Framework\Component\AuthorizationEndpoint\User\AuthenticationContextClassReferenceSupport;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;

final class AuthenticationContextClassReference implements Claim
{
    private const CLAIM_NAME = 'acr';

    /**
     * @var AuthenticationContextClassReferenceSupport
     */
    private $authenticationContextClassReferenceSupport;

    public function __construct(AuthenticationContextClassReferenceSupport $authenticationContextClassReferenceSupport)
    {
        $this->authenticationContextClassReferenceSupport = $authenticationContextClassReferenceSupport;
    }

    public function name(): string
    {
        return self::CLAIM_NAME;
    }

    public function isAvailableForUserAccount(UserAccount $userAccount, ?string $claimLocale): bool
    {
        return null !== $this->authenticationContextClassReferenceSupport->getAuthenticationContextClassReferenceFor($userAccount);
    }

    public function getForUserAccount(UserAccount $userAccount, ?string $claimLocale)
    {
        return $this->authenticationContextClassReferenceSupport->getAuthenticationContextClassReferenceFor($userAccount);
    }
}
