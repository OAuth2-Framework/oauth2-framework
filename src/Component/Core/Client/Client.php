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

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

/**
 * Class ClientCredentials.
 *
 * This class is used for every client types.
 * A client is a resource owner with a set of allowed grant types and can perform requests against
 * available endpoints.
 */
class Client implements ResourceOwner, \JsonSerializable
{
    /**
     * @var bool
     */
    private $deleted = false;

    /**
     * @var UserAccountId|null
     */
    private $ownerId = null;

    /**
     * @var ClientId|null
     */
    private $clientId = null;

    /**
     * @var DataBag
     */
    protected $parameters;

    /**
     * ClientCredentials constructor.
     */
    private function __construct()
    {
        $this->parameters = DataBag::create([]);
    }

    /**
     * @return Client
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param ClientId           $clientId
     * @param DataBag            $parameters
     * @param UserAccountId|null $ownerId
     *
     * @return Client
     */
    public function create(ClientId $clientId, DataBag $parameters, ? UserAccountId $ownerId): self
    {
        $clone = clone $this;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->ownerId = $ownerId;

        return $clone;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        $id = $this->getPublicId();
        if(!$id instanceof ClientId) {
            throw new \RuntimeException('Client not initialized.');
        }
        return $id;
    }

    /**
     * @return UserAccountId|null
     */
    public function getOwnerId(): ? UserAccountId
    {
        return $this->ownerId;
    }

    /**
     * @param UserAccountId $ownerId
     *
     * @return Client
     */
    public function withOwnerId(UserAccountId $ownerId): self
    {
        if ($this->getOwnerId()->getValue() === $ownerId->getValue()) {
            return $this;
        }

        $clone = clone $this;
        $clone->ownerId = $ownerId;

        return $clone;
    }

    /**
     * @param DataBag $parameters
     *
     * @return Client
     */
    public function withParameters(DataBag $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;

        return $clone;
    }

    /**
     * @return Client
     */
    public function markAsDeleted(): self
    {
        $clone = clone $this;
        $clone->deleted = true;

        return $clone;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param string $grant_type
     *
     * @return bool
     */
    public function isGrantTypeAllowed(string $grant_type): bool
    {
        $grant_types = $this->has('grant_types') ? $this->get('grant_types') : [];
        if (!is_array($grant_types)) {
            throw new \InvalidArgumentException('The metadata "grant_types" must be an array.');
        }

        return in_array($grant_type, $grant_types);
    }

    /**
     * @param string $response_type
     *
     * @return bool
     */
    public function isResponseTypeAllowed(string $response_type): bool
    {
        $response_types = $this->has('response_types') ? $this->get('response_types') : [];
        if (!is_array($response_types)) {
            throw new \InvalidArgumentException('The metadata "response_types" must be an array.');
        }

        return in_array($response_type, $response_types);
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return 'none' === $this->getTokenEndpointAuthenticationMethod();
    }

    /**
     * @return string
     */
    public function getTokenEndpointAuthenticationMethod(): string
    {
        if ($this->has('token_endpoint_auth_method')) {
            return $this->get('token_endpoint_auth_method');
        }

        return 'client_secret_basic';
    }

    /**
     * @return int
     */
    public function getClientCredentialsExpiresAt(): int
    {
        if ($this->has('client_secret_expires_at')) {
            return $this->get('client_secret_expires_at');
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function areClientCredentialsExpired(): bool
    {
        if (0 === $this->getClientCredentialsExpiresAt()) {
            return false;
        }

        return time() > $this->getClientCredentialsExpiresAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicId(): ResourceOwnerId
    {
        if (null === $this->clientId) {
            throw new \RuntimeException('Client not initialized.');
        }

        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->parameters->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('Configuration value with key "%s" does not exist.', $key));
        }

        return $this->parameters->get($key);
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $all = $this->parameters->all();
        $all['client_id'] = $this->getPublicId()->getValue();

        return $all;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            'type' => get_class($this),
            'client_id' => $this->getPublicId()->getValue(),
            'owner_id' => $this->getOwnerId() ? $this->getOwnerId()->getValue() : null,
            'parameters' => (object) $this->all(),
            'is_deleted' => $this->isDeleted(),
        ];

        return $data;
    }

    /**
     * @param \stdClass $json
     *
     * @return Client
     */
    public static function createFromJson(\stdClass $json): self
    {
        $clientId = ClientId::create($json->client_id);
        $ownerId = null !== $json->owner_id ? UserAccountId::create($json->owner_id) : null;
        $parameters = DataBag::create((array) $json->parameters);
        $deleted = $json->is_deleted;

        $client = new self();
        $client->clientId = $clientId;
        $client->ownerId = $ownerId;
        $client->parameters = $parameters;
        $client->deleted = $deleted;

        return $client;
    }
}
