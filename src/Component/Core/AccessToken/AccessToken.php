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

namespace OAuth2Framework\Component\Core\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\Event as AccessTokenEvent;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\Domain\DomainObject;

class AccessToken extends Token
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId = null;

    /**
     * @return AccessToken
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
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema';
    }

    /**
     * @param AccessTokenId         $accessTokenId
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param DataBag               $parameters
     * @param DataBag               $metadatas
     * @param \DateTimeImmutable    $expiresAt
     * @param ResourceServerId|null $resourceServerId
     *
     * @return AccessToken
     */
    public function create(AccessTokenId $accessTokenId, ResourceOwnerId $resourceOwnerId, ClientId $clientId, DataBag $parameters, DataBag $metadatas, \DateTimeImmutable $expiresAt, ? ResourceServerId $resourceServerId)
    {
        $clone = clone $this;
        $clone->accessTokenId = $accessTokenId;
        $clone->resourceOwnerId = $resourceOwnerId;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->metadatas = $metadatas;
        $clone->expiresAt = $expiresAt;
        $clone->resourceServerId = $resourceServerId;

        $event = AccessTokenEvent\AccessTokenCreatedEvent::create($accessTokenId, $resourceOwnerId, $clientId, $parameters, $metadatas, $expiresAt, $resourceServerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenId(): TokenId
    {
        if (null === $this->accessTokenId) {
            throw new \RuntimeException('Access token not initialized.');
        }

        return $this->accessTokenId;
    }

    /**
     * @return AccessToken
     */
    public function markAsRevoked(): self
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = AccessTokenEvent\AccessTokenRevokedEvent::create($clone->getTokenId());
        $clone->record($event);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'access_token_id' => $this->getTokenId()->getValue(),
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $accessTokenId = AccessTokenId::create($json->access_token_id);
        $resourceServerId = null !== $json->resource_server_id ? ResourceServerId::create($json->resource_server_id) : null;

        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at);
        $clientId = ClientId::create($json->client_id);
        $parameters = DataBag::create((array) $json->parameters);
        $metadatas = DataBag::create((array) $json->metadatas);
        $revoked = $json->is_revoked;
        $resourceOwnerClass = $json->resource_owner_class;
        if (!method_exists($resourceOwnerClass, 'create')) {
            throw new \InvalidArgumentException('Invalid resource owner.');
        }
        $resourceOwnerId = $resourceOwnerClass::create($json->resource_owner_id);

        $accessToken = new self();
        $accessToken->accessTokenId = $accessTokenId;
        $accessToken->resourceServerId = $resourceServerId;

        $accessToken->expiresAt = $expiresAt;
        $accessToken->clientId = $clientId;
        $accessToken->parameters = $parameters;
        $accessToken->metadatas = $metadatas;
        $accessToken->revoked = $revoked;
        $accessToken->resourceOwnerId = $resourceOwnerId;

        return $accessToken;
    }

    /**
     * @return array
     */
    public function getResponseData(): array
    {
        $data = $this->getParameters()->all();
        $data['access_token'] = $this->getTokenId()->getValue();
        $data['expires_in'] = $this->getExpiresIn();

        return $data;
    }

    /**
     * @param Event $event
     *
     * @return AccessToken
     */
    public function apply(Event $event): self
    {
        $map = $this->getEventMap();
        if (!array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if (null !== $this->clientId && $this->accessTokenId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this access token.');
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
    protected function applyAccessTokenCreatedEvent(AccessTokenEvent\AccessTokenCreatedEvent $event): self
    {
        $clone = clone $this;
        $clone->accessTokenId = $event->getAccessTokenId();
        $clone->resourceOwnerId = $event->getResourceOwnerId();
        $clone->clientId = $event->getClientId();
        $clone->parameters = $event->getParameters();
        $clone->metadatas = $event->getMetadatas();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->resourceServerId = $event->getResourceServerId();

        return $clone;
    }

    /**
     * @return AccessToken
     */
    protected function applyAccessTokenRevokedEvent(): self
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
