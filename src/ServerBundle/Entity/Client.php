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

namespace OAuth2Framework\ServerBundle\Entity;

use OAuth2Framework\Component\Core\Client\Client as ClientInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

/**
 * This class is used for every client types.
 * A client is a resource owner with a set of allowed grant types and can perform requests against
 * available endpoints.
 */
class Client implements ClientInterface
{
    protected $clientId;
    protected $ownerId;
    protected $parameter;
    protected $deleted;

    public function __construct(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId)
    {
        $this->clientId = $clientId;
        $this->parameter = $parameters;
        $this->ownerId = $ownerId;
        $this->deleted = false;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getOwnerId(): ?UserAccountId
    {
        return $this->ownerId;
    }

    public function setParameter(DataBag $parameter): void
    {
        $this->parameter = $parameter;
    }

    public function markAsDeleted(): void
    {
        $this->deleted = true;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function isGrantTypeAllowed(string $grant_type): bool
    {
        $grant_types = $this->has('grant_types') ? $this->get('grant_types') : [];
        if (!\is_array($grant_types)) {
            throw new \InvalidArgumentException('The metadata "grant_types" must be an array.');
        }

        return \in_array($grant_type, $grant_types, true);
    }

    public function isResponseTypeAllowed(string $response_type): bool
    {
        $response_types = $this->has('response_types') ? $this->get('response_types') : [];
        if (!\is_array($response_types)) {
            throw new \InvalidArgumentException('The metadata "response_types" must be an array.');
        }

        return \in_array($response_type, $response_types, true);
    }

    public function isPublic(): bool
    {
        return 'none' === $this->getTokenEndpointAuthenticationMethod();
    }

    public function getTokenEndpointAuthenticationMethod(): string
    {
        if ($this->has('token_endpoint_auth_method')) {
            return $this->get('token_endpoint_auth_method');
        }

        return 'client_secret_basic';
    }

    public function getClientCredentialsExpiresAt(): int
    {
        if ($this->has('client_secret_expires_at')) {
            return $this->get('client_secret_expires_at');
        }

        return 0;
    }

    public function areClientCredentialsExpired(): bool
    {
        if (0 === $this->getClientCredentialsExpiresAt()) {
            return false;
        }

        return \time() > $this->getClientCredentialsExpiresAt();
    }

    public function getPublicId(): ResourceOwnerId
    {
        if (null === $this->clientId) {
            throw new \RuntimeException('Client not initialized.');
        }

        return $this->clientId;
    }

    public function has(string $key): bool
    {
        return $this->parameter->has($key);
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(\Safe\sprintf('Configuration value with key "%s" does not exist.', $key));
        }

        return $this->parameter->get($key);
    }

    public function all(): array
    {
        $all = $this->parameter->all();
        $all['client_id'] = $this->getPublicId()->getValue();

        return $all;
    }

    public function jsonSerialize()
    {
        $data = [
            'client_id' => $this->getPublicId()->getValue(),
            'owner_id' => $this->getOwnerId() ? $this->getOwnerId()->getValue() : null,
            'parameters' => (object) $this->all(),
            'is_deleted' => $this->isDeleted(),
        ];

        return $data;
    }
}
