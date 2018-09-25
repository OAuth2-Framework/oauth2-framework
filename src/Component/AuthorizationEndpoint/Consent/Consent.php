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

namespace OAuth2Framework\Component\AuthorizationEndpoint;

class Consent
{
    private $clientId;
    private $userAccountId;
    private $scope;
    private $claims;

    public function __construct(string $clientId, string $userAccountId, string $scope, string $claims)
    {
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->scope = $scope;
        $this->claims = $claims;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getUserAccountId(): string
    {
        return $this->userAccountId;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function getClaims(): string
    {
        return $this->claims;
    }
}
