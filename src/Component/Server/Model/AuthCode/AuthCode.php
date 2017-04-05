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

namespace OAuth2Framework\Component\Server\Model\AuthCode;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Event\AuthCode as AuthCodeEvent;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\Token\Token;
use OAuth2Framework\Component\Server\Model\Token\TokenId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class AuthCode extends Token
{
    /**
     * @var AuthCodeId
     */
    private $authCodeId = null;

    /**
     * @var bool
     */
    private $issueRefreshToken = false;

    /**
     * @var array
     */
    private $queryParameters = [];

    /**
     * @var string
     */
    private $redirectUri = null;

    /**
     * @var bool
     */
    private $used = false;

    /**
     * @return AuthCode
     */
    public static function createEmpty(): AuthCode
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/auth-code/1.0/schema';
    }

    /**
     * @param AuthCodeId         $authCodeId
     * @param ClientId           $clientId
     * @param UserAccountId      $userAccountId
     * @param array              $queryParameters
     * @param string             $redirectUri
     * @param \DateTimeImmutable $expiresAt
     * @param DataBag            $parameters
     * @param DataBag            $metadatas
     * @param string[]           $scopes
     * @param bool               $withRefreshToken
     * @param ResourceServerId   $resourceServerId
     *
     * @return AuthCode
     */
    public function create(AuthCodeId $authCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? ResourceServerId $resourceServerId)
    {
        $clone = clone $this;
        $clone->authCodeId = $authCodeId;
        $clone->clientId = $clientId;
        $clone->resourceOwnerId = $userAccountId;
        $clone->queryParameters = $queryParameters;
        $clone->redirectUri = $redirectUri;
        $clone->authCodeId = $authCodeId;
        $clone->expiresAt = $expiresAt;
        $clone->parameters = $parameters;
        $clone->metadatas = $metadatas;
        $clone->scopes = $scopes;
        $clone->issueRefreshToken = $withRefreshToken;
        $clone->expiresAt = $expiresAt;
        $clone->resourceServerId = $resourceServerId;

        $event = AuthCodeEvent\AuthCodeCreatedEvent::create($authCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $scopes, $withRefreshToken, $resourceServerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenId(): TokenId
    {
        return $this->getAuthCodeId();
    }

    /**
     * @return AuthCodeId
     */
    public function getAuthCodeId(): AuthCodeId
    {
        Assertion::notNull($this->authCodeId, 'Authorization code not initialized.');

        return $this->authCodeId;
    }

    /**
     * @return array
     */
    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    /**
     * @return bool
     */
    public function isRefreshTokenIssued(): bool
    {
        return $this->issueRefreshToken;
    }

    /**
     * @return bool
     */
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * @return AuthCode
     */
    public function markAsUsed(): AuthCode
    {
        if (true === $this->used) {
            return $this;
        }
        $clone = clone $this;
        $clone->used = true;
        $event = AuthCodeEvent\AuthCodeMarkedAsUsedEvent::create($clone->getAuthCodeId());
        $clone->record($event);

        return $clone;
    }

    /**
     * @return AuthCode
     */
    public function markAsRevoked(): AuthCode
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = AuthCodeEvent\AuthCodeRevokedEvent::create($clone->getAuthCodeId());
        $clone->record($event);

        return $clone;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParameters;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getQueryParam(string $key)
    {
        Assertion::true($this->hasQueryParams($key), sprintf('Query parameter with key \'%s\' does not exist.', $key));

        return $this->queryParameters[$key];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasQueryParams(string $key): bool
    {
        return array_key_exists($key, $this->getQueryParams());
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        Assertion::notNull($this->authCodeId, 'Authorization code not initialized.');

        return $this->redirectUri;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getTokenId()->getValue(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $authCodeId = AuthCodeId::create($json->auth_code_id);
        $issueRefreshToken = (bool) $json->with_refresh_token;
        $queryParameters = (array) $json->query_parameters;
        $redirectUri = $json->redirect_uri;
        $resourceServerId = null !== $json->resource_server_id ? ResourceServerId::create($json->resource_server_id) : null;
        $used = $json->is_used;

        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at);
        $clientId = ClientId::create($json->client_id);
        $parameters = DataBag::createFromArray((array) $json->parameters);
        $metadatas = DataBag::createFromArray((array) $json->metadatas);
        $scopes = (array) $json->scopes;
        $revoked = $json->is_revoked;
        $resourceOwnerClass = $json->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->resource_owner_id);

        $authCode = new self();
        $authCode->authCodeId = $authCodeId;
        $authCode->issueRefreshToken = $issueRefreshToken;
        $authCode->queryParameters = $queryParameters;
        $authCode->redirectUri = $redirectUri;
        $authCode->used = $used;
        $authCode->resourceServerId = $resourceServerId;

        $authCode->expiresAt = $expiresAt;
        $authCode->clientId = $clientId;
        $authCode->parameters = $parameters;
        $authCode->metadatas = $metadatas;
        $authCode->scopes = $scopes;
        $authCode->revoked = $revoked;
        $authCode->resourceOwnerId = $resourceOwnerId;

        return $authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize() + [
                'auth_code_id' => $this->getAuthCodeId()->getValue(),
                'with_refresh_token' => $this->isRefreshTokenIssued(),
                'query_parameters' => (object) $this->getQueryParameters(),
                'redirect_uri' => $this->getRedirectUri(),
                'is_used' => $this->isUsed(),
            ];

        return $data;
    }

    /**
     * @param Event $event
     *
     * @return AuthCode
     */
    public function apply(Event $event): AuthCode
    {
        $map = $this->getEventMap();
        Assertion::keyExists($map, $event->getType(), 'Unsupported event.');
        if (null !== $this->authCodeId) {
            Assertion::eq($this->authCodeId, $event->getDomainId(), 'Event not applicable for this client.');
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
            AuthCodeEvent\AuthCodeCreatedEvent::class => 'applyAuthCodeCreatedEvent',
            AuthCodeEvent\AuthCodeMarkedAsUsedEvent::class => 'applyAuthCodeMarkedAsUsedEvent',
            AuthCodeEvent\AuthCodeRevokedEvent::class => 'applyAuthCodeRevokedEvent',
        ];
    }

    /**
     * @param AuthCodeEvent\AuthCodeCreatedEvent $event
     *
     * @return AuthCode
     */
    protected function applyAuthCodeCreatedEvent(AuthCodeEvent\AuthCodeCreatedEvent $event): AuthCode
    {
        $clone = clone $this;
        $clone->authCodeId = $event->getAuthCodeId();
        $clone->clientId = $event->getClientId();
        $clone->resourceOwnerId = $event->getUserAccountId();
        $clone->queryParameters = $event->getQueryParameters();
        $clone->redirectUri = $event->getRedirectUri();
        $clone->authCodeId = $event->getAuthCodeId();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->parameters = $event->getParameters();
        $clone->metadatas = $event->getMetadatas();
        $clone->scopes = $event->getScopes();
        $clone->issueRefreshToken = $event->issueRefreshToken();
        $clone->expiresAt = $event->getExpiresAt();
        $clone->resourceServerId = $event->getResourceServerId();

        return $clone;
    }

    /**
     * @param AuthCodeEvent\AuthCodeMarkedAsUsedEvent $event
     *
     * @return AuthCode
     */
    protected function applyAuthCodeMarkedAsUsedEvent(AuthCodeEvent\AuthCodeMarkedAsUsedEvent $event): AuthCode
    {
        $clone = clone $this;
        $clone->used = true;

        return $clone;
    }

    /**
     * @param AuthCodeEvent\AuthCodeRevokedEvent $event
     *
     * @return AuthCode
     */
    protected function applyAuthCodeRevokedEvent(AuthCodeEvent\AuthCodeRevokedEvent $event): AuthCode
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
