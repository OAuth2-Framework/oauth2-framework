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

class AuthorizationCodeMarkedAsUsedEvent extends Event
{
    private $authorizationCodeId;

    public function __construct(AuthorizationCodeId $authorizationCodeId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/marked-as-used/1.0/schema';
    }

    public static function create(AuthorizationCodeId $authorizationCodeId): self
    {
        return new self($authorizationCodeId);
    }

    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthorizationCodeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }
}
