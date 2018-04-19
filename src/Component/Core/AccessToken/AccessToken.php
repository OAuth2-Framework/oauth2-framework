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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\Token\Token;
use OAuth2Framework\Component\Core\Token\TokenId;

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
     * @param \stdClass $json
     *
     * @return AccessToken
     */
    public static function createFromJson(\stdClass $json): self
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
}
