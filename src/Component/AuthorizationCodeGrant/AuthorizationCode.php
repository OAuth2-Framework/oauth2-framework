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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use OAuth2Framework\Component\AuthorizationCodeGrant\Event as AuthorizationCodeEvent;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCode extends Token
{
    private $queryParameters;
    private $redirectUri;
    private $used;

    public function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        parent::__construct($authorizationCodeId, $clientId, $userAccountId, $parameter, $metadata, $expiresAt, $resourceServerId);
        $this->queryParameters = $queryParameters;
        $this->redirectUri = $redirectUri;
        $this->used = false;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/authorization-code/1.0/schema';
    }

    public function setTokenId(TokenId $tokenId): void
    {
        if (!$tokenId instanceof AuthorizationCodeId) {
            throw new \RuntimeException('The token ID must be an Authorization Code ID.');
        }
        parent::setTokenId($tokenId);
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(array $queryParameters): void
    {
        $this->queryParameters = $queryParameters;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    public function getQueryParam(string $key)
    {
        if (!$this->hasQueryParam($key)) {
            throw new \RuntimeException(\sprintf('Query parameter with key "%s" does not exist.', $key));
        }

        return $this->queryParameters[$key];
    }

    public function hasQueryParam(string $key): bool
    {
        return \array_key_exists($key, $this->getQueryParams());
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getTokenId()->getValue(),
        ];
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
            'auth_code_id' => $this->getTokenId()->getValue(),
            'query_parameters' => (object) $this->getQueryParameters(),
            'redirect_uri' => $this->getRedirectUri(),
            'is_used' => $this->isUsed(),
        ];

        return $data;
    }

    public function apply(Event $event): void
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \RuntimeException('Unsupported event.');
        }
        if ($this->getTokenId()->getValue() !== $event->getDomainId()->getValue()) {
            throw new \RuntimeException('Event not applicable for this authorization code.');
        }
        $method = $map[$event->getType()];

        $this->$method($event);
    }

    private function getEventMap(): array
    {
        return [
            AuthorizationCodeEvent\AuthorizationCodeCreatedEvent::class => 'applyAuthorizationCodeCreatedEvent',
            AuthorizationCodeEvent\AuthorizationCodeMarkedAsUsedEvent::class => 'applyAuthorizationCodeMarkedAsUsedEvent',
            AuthorizationCodeEvent\AuthorizationCodeRevokedEvent::class => 'applyAuthorizationCodeRevokedEvent',
        ];
    }

    protected function applyAuthorizationCodeCreatedEvent(AuthorizationCodeEvent\AuthorizationCodeCreatedEvent $event): void
    {
        $this->setTokenId($event->getAuthorizationCodeId());
        $this->setClientId($event->getClientId());
        $this->setResourceOwnerId($event->getUserAccountId());
        $this->setQueryParameters($event->getQueryParameters());
        $this->setRedirectUri($event->getRedirectUri());
        $this->setExpiresAt($event->getExpiresAt());
        $this->setParameter($event->getParameter());
        $this->setMetadata($event->getMetadata());
        $this->setExpiresAt($event->getExpiresAt());
        $this->setResourceServerId($event->getResourceServerId());
        $this->used = false;
    }

    protected function applyAuthorizationCodeMarkedAsUsedEvent(AuthorizationCodeEvent\AuthorizationCodeMarkedAsUsedEvent $event): void
    {
        $this->markAsUsed();
    }

    protected function applyAuthorizationCodeRevokedEvent(AuthorizationCodeEvent\AuthorizationCodeRevokedEvent $event): void
    {
        $this->markAsRevoked();
    }
}
