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

use OAuth2Framework\Component\Core\Client\Event as ClientEvent;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

/**
 * Class ClientCredentials.
 *
 * This class is used for every client types.
 * A client is a resource owner with a set of allowed grant types and can perform requests against
 * available endpoints.
 */
class Client implements ResourceOwner, DomainObject
{
    private $clientId;
    private $ownerId;
    protected $parameter;
    private $deleted;

    public function __construct(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId)
    {
        $this->clientId = $clientId;
        $this->parameter = $parameters;
        $this->ownerId = $ownerId;
        $this->deleted = false;
    }

    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema';
    }

    public function getClientId(): ClientId
    {
        $id = $this->getPublicId();
        if (!$id instanceof ClientId) {
            throw new \RuntimeException('Client not initialized.');
        }

        return $id;
    }

    public function getOwnerId(): ?UserAccountId
    {
        return $this->ownerId;
    }

    public function setOwnerId(UserAccountId $ownerId): void
    {
        $this->ownerId = $ownerId;
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
            throw new \InvalidArgumentException(\sprintf('Configuration value with key "%s" does not exist.', $key));
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
            '$schema' => $this->getSchema(),
            'type' => \get_class($this),
            'client_id' => $this->getPublicId()->getValue(),
            'owner_id' => $this->getOwnerId() ? $this->getOwnerId()->getValue() : null,
            'parameters' => (object) $this->all(),
            'is_deleted' => $this->isDeleted(),
        ];

        return $data;
    }

    public function apply(Event $event): void
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if (null !== $this->clientId && $this->clientId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this client.');
        }
        $method = $map[$event->getType()];
        $this->$method($event);
    }

    private function getEventMap(): array
    {
        return [
            ClientEvent\ClientCreatedEvent::class => 'applyClientCreatedEvent',
            ClientEvent\ClientOwnerChangedEvent::class => 'applyClientOwnerChangedEvent',
            ClientEvent\ClientDeletedEvent::class => 'applyClientDeletedEvent',
            ClientEvent\ClientParametersUpdatedEvent::class => 'applyClientParametersUpdatedEvent',
        ];
    }

    protected function applyClientCreatedEvent(ClientEvent\ClientCreatedEvent $event): void
    {
        $this->clientId = $event->getClientId();
        $this->ownerId = $event->getOwnerId();
        $this->parameter = $event->getParameters();
    }

    protected function applyClientOwnerChangedEvent(ClientEvent\ClientOwnerChangedEvent $event): void
    {
        $this->ownerId = $event->getNewOwnerId();
    }

    protected function applyClientDeletedEvent(ClientEvent\ClientDeletedEvent $event): void
    {
        $this->deleted = true;
    }

    protected function applyClientParametersUpdatedEvent(ClientEvent\ClientParametersUpdatedEvent $event): void
    {
        $this->parameter = $event->getParameters();
    }
}
