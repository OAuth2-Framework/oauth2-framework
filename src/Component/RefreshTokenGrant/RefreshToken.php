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

use OAuth2Framework\Component\RefreshTokenGrant\Event as RefreshTokenEvent;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\Domain\DomainObject;

class RefreshToken extends Token
{
    /**
     * @var RefreshTokenId|null
     */
    private $refreshTokenId = null;

    /**
     * @var AccessTokenId[]
     */
    private $accessTokenIds = [];

    /**
     * @return RefreshToken
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema';
    }

    /**
     * @param RefreshTokenId        $refreshTokenId
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param \DateTimeImmutable    $expiresAt
     * @param ResourceServerId|null $resourceServerId
     *
     * @return RefreshToken
     */
    public function create(RefreshTokenId $refreshTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId): self
    {
        $clone = clone $this;
        $clone->refreshTokenId = $refreshTokenId;
        $clone->resourceOwnerId = $resourceOwnerId;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->metadatas = $metadatas;
        $clone->expiresAt = $expiresAt;
        $clone->resourceServerId = $resourceServerId;

        $event = RefreshTokenEvent\RefreshTokenCreatedEvent::create($refreshTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenId(): TokenId
    {
        if (null === $this->refreshTokenId) {
            throw new \LogicException('Refresh token not initialized.');
        }

        return $this->refreshTokenId;
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return RefreshToken
     */
    public function addAccessToken(AccessTokenId $accessTokenId): self
    {
        $id = $accessTokenId->getValue();
        if (array_key_exists($id, $this->accessTokenIds)) {
            return $this;
        }

        $clone = clone $this;
        $clone->accessTokenIds[$id] = $accessTokenId;
        $event = RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent::create($clone->getTokenId(), $accessTokenId);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return AccessTokenId[]
     */
    public function getAccessTokenIds(): array
    {
        return $this->accessTokenIds;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        $data = $this->getParameters();
        $data = $data->with('access_token', $this->getTokenId()->getValue());
        $data = $data->with('expires_in', $this->getExpiresIn());
        if (!empty($this->getTokenId())) {
            $data = $data->with('refresh_token', $this->getTokenId());
        }

        return $data->all();
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $refreshTokenId = RefreshTokenId::create($json->refresh_token_id);
        $resourceServerId = null !== $json->resource_server_id ? ResourceServerId::create($json->resource_server_id) : null;
        $accessTokenIds = [];
        foreach ($json->access_token_ids as $access_token_id) {
            $accessTokenIds[$access_token_id] = AccessTokenId::create($access_token_id);
        }

        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at);
        $clientId = ClientId::create($json->client_id);
        $parameters = DataBag::create((array) $json->parameters);
        $metadatas = DataBag::create((array) $json->metadatas);
        $revoked = $json->is_revoked;
        $resourceOwnerClass = $json->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->resource_owner_id);

        $refreshToken = new self();
        $refreshToken->refreshTokenId = $refreshTokenId;
        $refreshToken->accessTokenIds = $accessTokenIds;
        $refreshToken->resourceServerId = $resourceServerId;

        $refreshToken->expiresAt = $expiresAt;
        $refreshToken->clientId = $clientId;
        $refreshToken->parameters = $parameters;
        $refreshToken->metadatas = $metadatas;
        $refreshToken->revoked = $revoked;
        $refreshToken->resourceOwnerId = $resourceOwnerId;

        return $refreshToken;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
                'refresh_token_id' => $this->getTokenId()->getValue(),
                'access_token_ids' => array_keys($this->getAccessTokenIds()),
                'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
            ];

        return $data;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @return RefreshToken
     */
    public function markAsRevoked(): self
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = RefreshTokenEvent\RefreshTokenRevokedEvent::create($clone->getTokenId());
        $clone->record($event);

        return $clone;
    }

    /**
     * @param Event $event
     *
     * @return RefreshToken
     */
    public function apply(Event $event): self
    {
        $map = $this->getEventMap();
        if (!array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if (null !== $this->refreshTokenId && $this->refreshTokenId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \RuntimeException('Event not applicable for this refresh token.');
        }
        $method = $map[$event->getType()];

        return $this->$method($event);
    }

    /**
     * @return array
     */
    private function getEventMap(): array
    {
        return [
            RefreshTokenEvent\RefreshTokenCreatedEvent::class => 'applyRefreshTokenCreatedEvent',
            RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent::class => 'applyAccessTokenAddedToRefreshTokenEvent',
            RefreshTokenEvent\RefreshTokenRevokedEvent::class => 'applyRefreshTokenRevokedEvent',
        ];
    }

    /**
     * @param RefreshTokenEvent\RefreshTokenCreatedEvent $event
     *
     * @return RefreshToken
     */
    protected function applyRefreshTokenCreatedEvent(RefreshTokenEvent\RefreshTokenCreatedEvent $event): self
    {
        $clone = clone $this;
        $clone->refreshTokenId = $event->getRefreshTokenId();
        $clone->resourceOwnerId = $event->getResourceOwnerId();
        $clone->clientId = $event->getClientId();
        $clone->parameters = $event->getParameters();
        $clone->metadatas = $event->getMetadatas();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->resourceServerId = $event->getResourceServerId();

        return $clone;
    }

    /**
     * @param RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent $event
     *
     * @return RefreshToken
     */
    protected function applyAccessTokenAddedToRefreshTokenEvent(RefreshTokenEvent\AccessTokenAddedToRefreshTokenEvent $event): self
    {
        $clone = clone $this;
        $clone->accessTokenIds[$event->getAccessTokenId()->getValue()] = $event->getAccessTokenId();

        return $clone;
    }

    /**
     * @param RefreshTokenEvent\RefreshTokenRevokedEvent $event
     *
     * @return RefreshToken
     */
    protected function applyRefreshTokenRevokedEvent(RefreshTokenEvent\RefreshTokenRevokedEvent $event): self
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
