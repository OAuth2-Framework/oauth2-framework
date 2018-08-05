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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Event;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class AuthorizationCodeRevokedEvent extends Event
{
    private $authorizationCodeId;

    public function __construct(AuthorizationCodeId $authorizationCodeId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/revoked/1.0/schema';
    }

    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }

    public function getDomainId(): Id
    {
        return $this->getAuthorizationCodeId();
    }

    public function getPayload()
    {
    }
}
