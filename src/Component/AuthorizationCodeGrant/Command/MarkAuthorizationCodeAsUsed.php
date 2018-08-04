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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Command;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;

class MarkAuthorizationCodeAsUsed
{
    private $authorizationCodeId;

    public function __construct(AuthorizationCodeId $authorizationCodeId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
    }

    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }
}
