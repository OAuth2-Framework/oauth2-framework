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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCode implements \JsonSerializable
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
     * @var TokenId
     */
    protected $tokenId;

    /**
     * @var \DateTimeImmutable
     */
    private $expiresAt;

    /**
     * @var ResourceOwnerId
     */
    private $resourceOwnerId;

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

    public function __construct(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameter, DataBag $metadata, ?ResourceServerId $resourceServerId)
    {
        $this->queryParameters = $queryParameters;
        $this->redirectUri = $redirectUri;
        $this->used = false;
        $this->tokenId = $authorizationCodeId;
        $this->resourceOwnerId = $userAccountId;
        $this->clientId = $clientId;
        $this->parameter = $parameter;
        $this->metadata = $metadata;
        $this->expiresAt = $expiresAt;
        $this->resourceServerId = $resourceServerId;
        $this->revoked = false;
    }

    public function getQueryParameters(): array
    {
        return $this->queryParameters;
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
            throw new \RuntimeException(\Safe\sprintf('Query parameter with key "%s" does not exist.', $key));
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

    public function toArray(): array
    {
        return [
            'code' => $this->getTokenId()->getValue(),
        ];
    }

    public function getTokenId(): TokenId
    {
        return $this->tokenId;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasExpired(): bool
    {
        return $this->expiresAt->getTimestamp() < \time();
    }

    public function getResourceOwnerId(): ResourceOwnerId
    {
        return $this->resourceOwnerId;
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

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function markAsRevoked(): void
    {
        $this->revoked = true;
    }

    public function getResourceServerId(): ?ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getExpiresIn(): int
    {
        $expiresAt = $this->expiresAt;
        if (null === $expiresAt) {
            return 0;
        }

        return $this->expiresAt->getTimestamp() - \time() < 0 ? 0 : $this->expiresAt->getTimestamp() - \time();
    }

    public function jsonSerialize()
    {
        $data = [
            'auth_code_id' => $this->getTokenId()->getValue(),
            'query_parameters' => (object) $this->getQueryParameters(),
            'redirect_uri' => $this->getRedirectUri(),
            'is_used' => $this->isUsed(),
            'expires_at' => $this->getExpiresAt()->getTimestamp(),
            'client_id' => $this->getClientId()->getValue(),
            'parameters' => (object) $this->getParameter()->all(),
            'metadatas' => (object) $this->getMetadata()->all(),
            'is_revoked' => $this->isRevoked(),
            'resource_owner_id' => $this->getResourceOwnerId()->getValue(),
            'resource_owner_class' => \get_class($this->getResourceOwnerId()),
            'resource_server_id' => $this->getResourceServerId() ? $this->getResourceServerId()->getValue() : null,
        ];

        return $data;
    }
}
