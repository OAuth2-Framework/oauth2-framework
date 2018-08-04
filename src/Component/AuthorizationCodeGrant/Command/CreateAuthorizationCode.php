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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Command;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class CreateAuthorizationCode
{
    private $authorizationCodeId;
    private $clientId;
    private $userAccountId;
    private $queryParameter;
    private $redirectUri;
    private $expiresAt;
    private $parameter;
    private $metadata;
    private $resourceServerId;

    public function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameter, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        $this->authorizationCodeId = $authorizationCodeId;
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->queryParameter = $queryParameter;
        $this->redirectUri = $redirectUri;
        $this->expiresAt = $expiresAt;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->resourceServerId = $resourceServerId;
    }

    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getQueryParameter(): array
    {
        return $this->queryParameter;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getParameter(): DataBag
    {
        return $this->parameter;
    }

    public function getMetadata(): DataBag
    {
        return $this->metadata;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }
}
