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

class AccessToken extends Token
{
    public function __construct(AccessTokenId $accessTokenId, ClientId $clientId, ResourceOwnerId $resourceOwnerId, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        parent::__construct($accessTokenId, $clientId, $resourceOwnerId, $parameter, $metadata, $expiresAt, $resourceServerId);
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/access-token/1.0/schema';
    }

    public function setTokenId(TokenId $tokenId): void
    {
        if (!$tokenId instanceof AccessTokenId) {
            throw new \RuntimeException('The token ID must be an Access Token ID.');
        }
        parent::setTokenId($tokenId);
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'access_token_id' => $this->getTokenId()->getValue(),
        ];

        return $data;
    }

    public function getResponseData(): array
    {
        $data = $this->getParameter()->all();
        $data['access_token'] = $this->getTokenId()->getValue();
        $data['expires_in'] = $this->getExpiresIn();

        return $data;
    }

    public function apply(Event $event): void
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if ($this->getTokenId()->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this access token.');
        }
        $method = $map[$event->getType()];
        $this->$method($event);
    }

    private function getEventMap(): array
    {
        return [
            AccessTokenEvent\AccessTokenCreatedEvent::class => 'applyAccessTokenCreatedEvent',
            AccessTokenEvent\AccessTokenRevokedEvent::class => 'applyAccessTokenRevokedEvent',
        ];
    }

    protected function applyAccessTokenCreatedEvent(AccessTokenEvent\AccessTokenCreatedEvent $event): void
    {
        $this->setTokenId($event->getAccessTokenId());
        $this->setResourceOwnerId($event->getResourceOwnerId());
        $this->setClientId($event->getClientId());
        $this->setParameter($event->getParameter());
        $this->setMetadata($event->getMetadata());
        $this->setExpiresAt($event->getExpiresAt());
        $this->setResourceServerId($event->getResourceServerId());
    }

    protected function applyAccessTokenRevokedEvent(): void
    {
        $this->markAsRevoked();
    }
}
