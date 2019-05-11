<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use Assert\Assertion;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

abstract class AbstractAuthorizationCode implements AuthorizationCode
{
    /**
     * @var array
     */
    private $queryParameters;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var bool
     */
    private $used;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameter;

    /**
     * @var DataBag
     */
    private $metadata;

    /**
     * @var bool
     */
    private $revoked;

    /**
     * @var ResourceServerId|null
     */
    private $resourceServerId;

    public function __construct(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        $this->queryParameters = $queryParameters;
        $this->redirectUri = $redirectUri;
        $this->used = false;
        $this->userAccountId = $userAccountId;
        $this->clientId = $clientId;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
        $this->revoked = false;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
    }

    public function getQueryParameter(string $key)
    {
        Assertion::true($this->hasQueryParameter($key), \Safe\sprintf('Query parameter with key "%s" does not exist.', $key));

        return $this->queryParameters[$key];
    }

    public function hasQueryParameter(string $key): bool
    {
        return \array_key_exists($key, $this->getQueryParameters());
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->getId()->getValue(),
        ];
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < \time();
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

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresAt->getTimestamp() - \time() < 0 ? 0 : $this->expiresAt->getTimestamp() - \time();
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): void
    {
        $this->revoked = true;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'auth_code_id' => $this->getId()->getValue(),
            'query_parameters' => (object) $this->getQueryParameters(),
            'redirect_uri' => $this->getRedirectUri(),
            'is_used' => $this->isUsed(),
            'is_revoked' => $this->isRevoked(),
            'expires_at' => $this->getExpiresAt()->getTimestamp(),
            'client_id' => $this->getClientId()->getValue(),
            'parameters' => (object) $this->getParameter()->all(),
            'metadatas' => (object) $this->getMetadata()->all(),
            'resource_owner_id' => $this->getUserAccountId()->getValue(),
            'resource_owner_class' => \get_class($this->getUserAccountId()),
            'resource_server_id' => null !== $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }
}
