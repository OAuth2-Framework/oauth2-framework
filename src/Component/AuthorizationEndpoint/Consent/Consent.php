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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Consent;

class Consent
{
    private string $clientId;

    private string $userAccountId;

    private string $requestedScope;

    private ?string $grantedScope = null;

    private string $requestedClaims;

    private ?string $grantedClaims = null;

    public function __construct(string $clientId, string $userAccountId, string $scope, string $claims)
    {
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->requestedScope = $scope;
        $this->requestedClaims = $claims;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUserAccountId(): string
    {
        return $this->userAccountId;
    }

    public function getRequestedScope(): string
    {
        return $this->requestedScope;
    }

    public function getRequestedClaims(): string
    {
        return $this->requestedClaims;
    }

    public function getGrantedScope(): string
    {
        return $this->grantedScope;
    }

    public function setGrantedScope(string $grantedScope): void
    {
        $this->grantedScope = $grantedScope;
    }

    public function getGrantedClaims(): string
    {
        return $this->grantedClaims;
    }

    public function setGrantedClaims(string $grantedClaims): void
    {
        $this->grantedClaims = $grantedClaims;
    }
}
