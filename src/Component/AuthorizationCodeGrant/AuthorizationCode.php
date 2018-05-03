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
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class AuthorizationCode extends Token
{
    /**
     * @var AuthorizationCodeId
     */
    private $authorizationCodeId = null;

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
     * @return AuthorizationCode
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param AuthorizationCodeId $authorizationCodeId
     * @param ClientId            $clientId
     * @param UserAccountId       $userAccountId
     * @param array               $queryParameters
     * @param string              $redirectUri
     * @param \DateTimeImmutable  $expiresAt
     * @param DataBag             $parameters
     * @param DataBag             $metadatas
     * @param ResourceServerId    $resourceServerId
     *
     * @return AuthorizationCode
     */
    public function create(AuthorizationCodeId $authorizationCodeId, ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, \DateTimeImmutable $expiresAt, DataBag $parameters, DataBag $metadatas, ? ResourceServerId $resourceServerId)
    {
        $clone = clone $this;
        $clone->authorizationCodeId = $authorizationCodeId;
        $clone->clientId = $clientId;
        $clone->resourceOwnerId = $userAccountId;
        $clone->queryParameters = $queryParameters;
        $clone->redirectUri = $redirectUri;
        $clone->authorizationCodeId = $authorizationCodeId;
        $clone->expiresAt = $expiresAt;
        $clone->parameters = $parameters;
        $clone->metadatas = $metadatas;
        $clone->expiresAt = $expiresAt;
        $clone->resourceServerId = $resourceServerId;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenId(): TokenId
    {
        if (null === $this->authorizationCodeId) {
            throw new \RuntimeException('Authorization code not initialized.');
        }

        return $this->authorizationCodeId;
    }

    /**
     * @return AuthorizationCodeId
     */
    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        $id = $this->getTokenId();
        if (!$id instanceof AuthorizationCodeId) {
            throw new \RuntimeException('Authorization code not initialized.');
        }

        return $this->authorizationCodeId;
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
    public function isUsed(): bool
    {
        return $this->used;
    }

    /**
     * @return AuthorizationCode
     */
    public function markAsUsed(): self
    {
        if (true === $this->used) {
            return $this;
        }
        $clone = clone $this;
        $clone->used = true;

        return $clone;
    }

    /**
     * @return AuthorizationCode
     */
    public function markAsRevoked(): self
    {
        $clone = clone $this;
        $clone->revoked = true;

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
        if ($this->hasQueryParams($key)) {
            throw new \RuntimeException(sprintf('Query parameter with key "%s" does not exist.', $key));
        }

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
        if (null === $this->authorizationCodeId) {
            throw new \RuntimeException('Authorization code not initialized.');
        }

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
     * @param \stdClass $json
     *
     * @return AuthorizationCode
     */
    public static function createFromJson(\stdClass $json): self
    {
        $authorizationCodeId = AuthorizationCodeId::create($json->auth_code_id);
        $queryParameters = (array) $json->query_parameters;
        $redirectUri = $json->redirect_uri;
        $resourceServerId = null !== $json->resource_server_id ? ResourceServerId::create($json->resource_server_id) : null;
        $used = $json->is_used;

        $expiresAt = \DateTimeImmutable::createFromFormat('U', (string) $json->expires_at);
        $clientId = ClientId::create($json->client_id);
        $parameters = DataBag::create((array) $json->parameters);
        $metadatas = DataBag::create((array) $json->metadatas);
        $revoked = $json->is_revoked;
        $resourceOwnerClass = $json->resource_owner_class;
        $resourceOwnerId = $resourceOwnerClass::create($json->resource_owner_id);

        $authorizationCode = new self();
        $authorizationCode->authorizationCodeId = $authorizationCodeId;
        $authorizationCode->queryParameters = $queryParameters;
        $authorizationCode->redirectUri = $redirectUri;
        $authorizationCode->used = $used;
        $authorizationCode->resourceServerId = $resourceServerId;

        $authorizationCode->expiresAt = $expiresAt;
        $authorizationCode->clientId = $clientId;
        $authorizationCode->parameters = $parameters;
        $authorizationCode->metadatas = $metadatas;
        $authorizationCode->revoked = $revoked;
        $authorizationCode->resourceOwnerId = $resourceOwnerId;

        return $authorizationCode;
    }

    /**
     * {@inheritdoc}
     */
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
}
