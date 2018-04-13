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

namespace OAuth2Framework\Component\RefreshTokenGrant\Event;

use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Core\Domain\DomainObject;

class RefreshTokenRevokedEvent extends Event
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

    /**
     * RefreshTokenRevokedEvent constructor.
     *
     * @param RefreshTokenId $refreshTokenId
     */
    protected function __construct(RefreshTokenId $refreshTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/revoked/1.0/schema';
    }

    /**
     * @param RefreshTokenId $refreshTokenId
     *
     * @return RefreshTokenRevokedEvent
     */
    public static function create(RefreshTokenId $refreshTokenId): self
    {
        return new self($refreshTokenId);
    }

    /**
     * @return RefreshTokenId
     */
    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getRefreshTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $refreshTokenId = RefreshTokenId::create($json->domain_id);

        return new self($refreshTokenId);
    }
}
