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

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Core\Domain\DomainObject;

class AccessTokenAddedToRefreshTokenEvent extends Event
{
    /**
     * @var RefreshTokenId
     */
    private $refreshTokenId;

    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * AccessTokenAddedToRefreshTokenEvent constructor.
     *
     * @param RefreshTokenId $refreshTokenId
     * @param AccessTokenId  $accessTokenId
     */
    protected function __construct(RefreshTokenId $refreshTokenId, AccessTokenId $accessTokenId)
    {
        $this->refreshTokenId = $refreshTokenId;
        $this->accessTokenId = $accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema';
    }

    /**
     * @param RefreshTokenId $refreshTokenId
     * @param AccessTokenId  $accessTokenId
     *
     * @return AccessTokenAddedToRefreshTokenEvent
     */
    public static function create(RefreshTokenId $refreshTokenId, AccessTokenId $accessTokenId): self
    {
        return new self($refreshTokenId, $accessTokenId);
    }

    /**
     * @return RefreshTokenId
     */
    public function getRefreshTokenId(): RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
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
        return (object) [
            'access_token_id' => $this->accessTokenId->getValue(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $refreshTokenId = RefreshTokenId::create($json->domain_id);
        $accessTokenId = AccessTokenId::create($json->payload->access_token_id);

        return new self($refreshTokenId, $accessTokenId);
    }
}
