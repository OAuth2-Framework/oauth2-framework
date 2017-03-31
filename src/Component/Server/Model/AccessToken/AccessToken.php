<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\AccessToken;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Event\AccessToken as AccessTokenEvent;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\RefreshToken\RefreshTokenId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\Token\Token;
use OAuth2Framework\Component\Server\Model\Token\TokenId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class AccessToken extends Token
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId = null;

    /**
     * @var null|RefreshTokenId
     */
    private $refreshTokenId = null;

    /**
     * @return AccessToken
     */
    public static function createEmpty(): AccessToken
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema';
    }

    /**
     * @param AccessTokenId         $accessTokenId
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param string[]              $scopes
     * @param \DateTimeImmutable    $expiresAt
     * @param RefreshTokenId|null   $refreshTokenId
     * @param ResourceServerId|null $resourceServerId
     *
     * @return AccessToken
     */
    public function create(AccessTokenId $accessTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, array $scopes, \DateTimeImmutable $expiresAt, ?RefreshTokenId $refreshTokenId, ?ResourceServerId $resourceServerId)
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessTokenId;
        $clone->resourceOwnerId = $resourceOwnerId;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->metadatas = $metadatas;
        $clone->scopes = $scopes;
        $clone->expiresAt = $expiresAt;
        $clone->refreshTokenId = $refreshTokenId;
        $clone->resourceServerId = $resourceServerId;

        $event = AccessTokenEvent\AccessTokenCreatedEvent::create($accessTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $scopes, $expiresAt, $refreshTokenId, $resourceServerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        Assertion::notNull($this->accessTokenId, 'Access token not initialized.');

        return $this->accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenId(): TokenId
    {
        return $this->getAccessTokenId();
    }

    /**
     * @return null|RefreshTokenId
     */
    public function getRefreshTokenId(): ?RefreshTokenId
    {
        return $this->refreshTokenId;
    }

    /**
     * @return AccessToken
     */
    public function markAsRevoked(): AccessToken
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = AccessTokenEvent\AccessTokenRevokedEvent::create($clone->getAccessTokenId());
        $clone->record($event);

        return $clone;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        $data = $this->getParameters();
        $data = $data->withParameters([
            'access_token' => $this->getTokenId()->getValue(),
            'expires_in' => $this->getExpiresIn(),
        ]);
        if (!empty($this->getScopes())) {
            $data = $data->with('scope', implode(' ', $this->getScopes()));
        }
        if (!empty($this->getRefreshTokenId())) {
            $data = $data->with('refresh_token', $this->getRefreshTokenId());
        }

        return $data->all();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'access_token_id' => $this->getAccessTokenId()->getValue(),
            'refresh_token_id' => $this->getRefreshTokenId() ? $this->getRefreshTokenId()->getValue() : null,
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $accessTokenId = AccessTokenId::create($json->access_token_id);
        $refreshTokenId = null !== $json->refresh_token_id ? RefreshTokenId::create($json->refresh_token_id) : null;
        $resourceServerId = null !== $json->resource_server_id ? ResourceServerId::create($json->resource_server_id) : null;

        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at);
        $clientId = ClientId::create($json->client_id);
        $parameters = DataBag::createFromArray((array) $json->parameters);
        $metadatas = DataBag::createFromArray((array) $json->metadatas);
        $scopes = (array) $json->scopes;
        $revoked = $json->is_revoked;
        $resourceOwnerClass = $json->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->resource_owner_id);

        $accessToken = new self();
        $accessToken->accessTokenId = $accessTokenId;
        $accessToken->refreshTokenId = $refreshTokenId;
        $accessToken->resourceServerId = $resourceServerId;

        $accessToken->expiresAt = $expiresAt;
        $accessToken->clientId = $clientId;
        $accessToken->parameters = $parameters;
        $accessToken->metadatas = $metadatas;
        $accessToken->scopes = $scopes;
        $accessToken->revoked = $revoked;
        $accessToken->resourceOwnerId = $resourceOwnerId;

        return $accessToken;
    }

    /**
     * @param Event $event
     *
     * @return AccessToken
     */
    public function apply(Event $event): AccessToken
    {
        $map = $this->getEventMap();
        Assertion::keyExists($map, $event->getType(), 'Unsupported event.');
        if (null !== $this->clientId) {
            Assertion::eq($this->accessTokenId, $event->getDomainId(), 'Event not applicable for this access token.');
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
            AccessTokenEvent\AccessTokenCreatedEvent::class => 'applyAccessTokenCreatedEvent',
            AccessTokenEvent\AccessTokenRevokedEvent::class => 'applyAccessTokenRevokedEvent',
        ];
    }

    /**
     * @param AccessTokenEvent\AccessTokenCreatedEvent $event
     *
     * @return AccessToken
     */
    protected function applyAccessTokenCreatedEvent(AccessTokenEvent\AccessTokenCreatedEvent $event): AccessToken
    {
        $clone = clone $this;
        $clone->accessTokenId = $event->getAccessTokenId();
        $clone->resourceOwnerId = $event->getResourceOwnerId();
        $clone->clientId = $event->getClientId();
        $clone->parameters = $event->getParameters();
        $clone->metadatas = $event->getMetadatas();
        $clone->scopes = $event->getScopes();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->refreshTokenId = $event->getRefreshTokenId();
        $clone->resourceServerId = $event->getResourceServerId();

        return $clone;
    }

    /**
     * @return AccessToken
     */
    protected function applyAccessTokenRevokedEvent(): AccessToken
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
