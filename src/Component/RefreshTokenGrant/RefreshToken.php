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

namespace OAuth2Framework\Component\RefreshTokenGrant;

use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\RefreshTokenGrant\Event as RefreshTokenEvent;

class RefreshToken extends Token
{
    /**
     * @var AccessTokenId[]
     */
    private $accessTokenIds = [];

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema';
    }

    public function setTokenId(TokenId $tokenId): void
    {
        if (!$tokenId instanceof RefreshTokenId) {
            throw new \RuntimeException('The token ID must be an Refresh Token ID.');
        }
        parent::setTokenId($tokenId);
    }

    public function addAccessToken(AccessTokenId $accessTokenId): void
    {
        $id = $accessTokenId->getValue();
        if (!\array_key_exists($id, $this->accessTokenIds)) {
            $this->accessTokenIds[$id] = $accessTokenId;
        }
    }

    /**
     * @return AccessTokenId[]
     */
    public function getAccessTokenIds(): array
    {
        return $this->accessTokenIds;
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter();
        $data->with('access_token', $this->getTokenId()->getValue());
        $data->with('expires_in', $this->getExpiresIn());
        if (!empty($this->getTokenId())) {
            $data = $data->with('refresh_token', $this->getTokenId());
        }

        return $data->all();
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'refresh_token_id' => $this->getTokenId()->getValue(),
            'access_token_ids' => \array_keys($this->getAccessTokenIds()),
            'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }

    public function apply(Event $event): void
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if ($this->getTokenId()->getValue() !== $event->getDomainId()->getValue()) {
            throw new \RuntimeException('Event not applicable for this refresh token.');
        }
        $method = $map[$event->getType()];
        $this->$method($event);
    }

    private function getEventMap(): array
    {
        return [
            RefreshTokenEvent\RefreshTokenCreatedEvent::class => 'applyRefreshTokenCreatedEvent',
            RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent::class => 'applyAccessTokenAddedToRefreshTokenEvent',
            RefreshTokenEvent\RefreshTokenRevokedEvent::class => 'applyRefreshTokenRevokedEvent',
        ];
    }

    protected function applyRefreshTokenCreatedEvent(RefreshTokenEvent\RefreshTokenCreatedEvent $event): void
    {
        $this->setTokenId($event->getRefreshTokenId());
        $this->setResourceOwnerId($event->getResourceOwnerId());
        $this->setClientId($event->getClientId());
        $this->setParameter($event->getParameter());
        $this->setMetadata($event->getMetadata());
        $this->setExpiresAt($event->getExpiresAt());
        $this->setResourceServerId($event->getResourceServerId());
    }

    protected function applyAccessTokenAddedToRefreshTokenEvent(RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent $event): void
    {
        $this->accessTokenIds[$event->getAccessTokenId()->getValue()] = $event->getAccessTokenId();
    }

    protected function applyRefreshTokenRevokedEvent(RefreshTokenEvent\RefreshTokenRevokedEvent $event): void
    {
        $this->markAsRevoked();
    }
}
