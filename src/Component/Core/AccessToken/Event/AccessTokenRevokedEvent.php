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

namespace OAuth2Framework\Component\Core\AccessToken\Event;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;

class AccessTokenRevokedEvent extends Event
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * AccessTokenRevokedEvent constructor.
     */
    protected function __construct(AccessTokenId $accessTokenId)
    {
        $this->accessTokenId = $accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/revoked/1.0/schema';
    }

    /**
     * @return AccessTokenRevokedEvent
     */
    public static function create(AccessTokenId $accessTokenId): self
    {
        return new self($accessTokenId);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $accessTokenId = new AccessTokenId($json->domain_id);

        return new self($accessTokenId);
    }

    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAccessTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }
}
