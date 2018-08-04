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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Event;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCodeCreatedEvent extends Event
{
    private $authorizationCodeId;
    private $expiresAt;
    private $userAccountId;
    private $clientId;
    private $parameter;
    private $metadata;
    private $queryParameters;
    private $redirectUri;
    private $resourceServerId;

    public function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
        $this->userAccountId = $userAccountId;
        $this->clientId = $clientId;
        $this->expiresAt = $expiresAt;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->redirectUri = $redirectUri;
        $this->queryParameters = $queryParameters;
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/created/1.0/schema';
    }

    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getParameter(): DataBag
    {
        return $this->parameter;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthorizationCodeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'user_account_id' => $this->userAccountId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'expires_at' => $this->expiresAt->getTimestamp(),
            'parameter' => (object) $this->parameter->all(),
            'metadata' => (object) $this->metadata->all(),
            'redirect_uri' => $this->redirectUri,
            'query_parameters' => (object) $this->queryParameters,
            'resource_server_id' => $this->resourceServerId ? $this->resourceServerId->getValue() : null,
        ];
    }
}
