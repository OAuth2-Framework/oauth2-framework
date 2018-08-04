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
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

/**
 * Class ClientCredentials.
 *
 * This class is used for every client types.
 * A client is a resource owner with a set of allowed grant types and can perform requests against
 * available endpoints.
 */
class Client implements ResourceOwner, ContainsRecordedMessages, DomainObject
{
    use PrivateMessageRecorderCapabilities;

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
        $this->parameters = new DataBag([]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema';
    }

    /**
     * @return Client
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @return Client
     */
    public function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): self
    {
        $clone = clone $this;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->ownerId = $ownerId;

        $event = ClientEvent\ClientCreatedEvent::create($clone->clientId, $parameters, $ownerId);
        $clone->record($event);

        return $clone;
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

    /**
     * @return Client
     */
    public function withOwnerId(UserAccountId $ownerId): self
    {
        if ($this->getOwnerId()->getValue() === $ownerId->getValue()) {
            return $this;
        }

        $clone = clone $this;
        $clone->ownerId = $ownerId;
        $event = ClientEvent\ClientOwnerChangedEvent::create($clone->getClientId(), $ownerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return Client
     */
    public function withParameters(DataBag $parameters): self
    {
        $clone = clone $this;
        $clone->parameters = $parameters;
        $event = ClientEvent\ClientParametersUpdatedEvent::create($clone->getClientId(), $parameters);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return Client
     */
    public function markAsDeleted(): self
    {
        $clone = clone $this;
        $clone->deleted = true;
        $event = ClientEvent\ClientDeletedEvent::create($clone->getClientId());
        $clone->record($event);

        return $clone;
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
            throw new \InvalidArgumentException(\sprintf('Configuration value with key "%s" does not exist.', $key));
        }

        return $this->parameters->get($key);
    }

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
            '$schema' => $this->getSchema(),
            'type' => \get_class($this),
            'client_id' => $this->getPublicId()->getValue(),
            'owner_id' => $this->getOwnerId() ? $this->getOwnerId()->getValue() : null,
            'parameters' => (object) $this->all(),
            'is_deleted' => $this->isDeleted(),
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = new ClientId($json->client_id);
        $ownerId = null !== $json->owner_id ? new UserAccountId($json->owner_id) : null;
        $parameters = new DataBag((array) $json->parameters);
        $deleted = $json->is_deleted;

        $client = new self();
        $client->clientId = $clientId;
        $client->ownerId = $ownerId;
        $client->parameters = $parameters;
        $client->deleted = $deleted;

        return $client;
    }

    /**
     * @return Client
     */
    public function apply(Event $event): self
    {
        $map = $this->getEventMap();
        if (!\array_key_exists($event->getType(), $map)) {
            throw new \InvalidArgumentException('Unsupported event.');
        }
        if (null !== $this->clientId && $this->clientId->getValue() !== $event->getDomainId()->getValue()) {
            throw new \InvalidArgumentException('Event not applicable for this client.');
        }
        $method = $map[$event->getType()];

        return $this->$method($event);
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

    /**
     * @return Client
     */
    protected function applyClientCreatedEvent(ClientEvent\ClientCreatedEvent $event): self
    {
        $clone = clone $this;
        $clone->clientId = $event->getClientId();
        $clone->ownerId = $event->getOwnerId();
        $clone->parameters = $event->getParameters();

        return $clone;
    }

    /**
     * @return Client
     */
    protected function applyClientOwnerChangedEvent(ClientEvent\ClientOwnerChangedEvent $event): self
    {
        $clone = clone $this;
        $clone->ownerId = $event->getNewOwnerId();

        return $clone;
    }

    /**
     * @return Client
     */
    protected function applyClientDeletedEvent(ClientEvent\ClientDeletedEvent $event): self
    {
        $clone = clone $this;
        $clone->deleted = true;

        return $clone;
    }

    /**
     * @return Client
     */
    protected function applyClientParametersUpdatedEvent(ClientEvent\ClientParametersUpdatedEvent $event): self
    {
        $clone = clone $this;
        $clone->parameters = $event->getParameters();

        return $clone;
    }
}
