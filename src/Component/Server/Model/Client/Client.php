<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Model\Client;

use Assert\Assertion;
use Jose\Factory\JWKFactory;
use Jose\Object\JWK;
use Jose\Object\JWKSet;
use Jose\Object\JWKSetInterface;
use OAuth2Framework\Component\Server\Event\Client as ClientEvent;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

/**
 * Class Client.
 *
 * This class is used for every client types.
 * A client is a resource owner with a set of allowed grant types and can perform requests against
 * available endpoints.
 */
final class Client implements ResourceOwnerInterface, ContainsRecordedMessages, DomainObjectInterface
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
     * Client constructor.
     */
    private function __construct()
    {
        $this->parameters = DataBag::createFromArray([]);
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
    public static function createEmpty(): Client
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
    public function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): Client
    {
        $clone = clone $this;
        $clone->clientId = $clientId;
        $clone->parameters = $parameters;
        $clone->ownerId = $ownerId;

        $event = ClientEvent\ClientCreatedEvent::create($clone->clientId, $parameters, $ownerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return UserAccountId|null
     */
    public function getOwnerId(): ?UserAccountId
    {
        return $this->ownerId;
    }

    /**
     * @param UserAccountId $ownerId
     *
     * @return Client
     */
    public function withOwnerId(UserAccountId $ownerId): Client
    {
        $clone = clone $this;
        $clone->ownerId = $ownerId;
        $event = ClientEvent\ClientOwnerChangedEvent::create($clone->getPublicId(), $ownerId);
        $clone->record($event);

        return $clone;
    }

    /**
     * @param DataBag $parameters
     *
     * @return Client
     */
    public function withParameters(DataBag $parameters): Client
    {
        $clone = clone $this;
        $clone->parameters = $parameters;
        $event = ClientEvent\ClientParametersUpdatedEvent::create($clone->getPublicId(), $parameters);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return Client
     */
    public function markAsDeleted(): Client
    {
        $clone = clone $this;
        $clone->deleted = true;
        $event = ClientEvent\ClientDeletedEvent::create($clone->getPublicId());
        $clone->record($event);

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
        Assertion::string($grant_type, 'Argument must be a string.');
        $grant_types = $this->has('grant_types') ? $this->get('grant_types') : [];
        Assertion::isArray($grant_types, 'The metadata \'grant_types\' must be an array.');

        return in_array($grant_type, $grant_types);
    }

    /**
     * @param string $response_type
     *
     * @return bool
     */
    public function isResponseTypeAllowed(string $response_type): bool
    {
        Assertion::string($response_type, 'Argument must be a string.');
        $response_types = $this->has('response_types') ? $this->get('response_types') : [];
        Assertion::isArray($response_types, 'The metadata \'response_types\' must be an array.');

        return in_array($response_type, $response_types);
    }

    /**
     * @param string $token_type
     *
     * @return bool
     */
    public function isTokenTypeAllowed(string $token_type): bool
    {
        Assertion::string($token_type, 'Argument must be a string.');
        if (!$this->has('token_types')) {
            return true;
        }
        $token_types = $this->get('token_types');
        Assertion::isArray($token_types, 'The metadata \'token_types\' must be an array.');

        return in_array($token_type, $token_types);
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return 'none' === $this->getTokenEndpointAuthMethod();
    }

    /**
     * @return string
     */
    public function getTokenEndpointAuthMethod(): string
    {
        if ($this->has('token_endpoint_auth_method')) {
            return $this->get('token_endpoint_auth_method');
        }

        return 'client_secret_basic';
    }

    /**
     * @return int
     */
    public function getClientSecretExpiresAt(): int
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
        if (0 === $this->getClientSecretExpiresAt()) {
            return false;
        }

        return time() > $this->getClientSecretExpiresAt();
    }

    /**
     * @return bool
     */
    public function hasPublicKeySet(): bool
    {
        return $this->has('jwks') || $this->has('jwks_uri') || $this->has('client_secret');
    }

    /**
     * @return JWKSetInterface
     */
    public function getPublicKeySet(): JWKSetInterface
    {
        Assertion::true($this->hasPublicKeySet(), 'The client has no public key set');

        $jwkset = null;
        if ($this->has('jwks')) {
            $jwkset = new JWKSet($this->get('jwks'));
        }
        if ($this->has('jwks_uri')) {
            $jwkset = JWKFactory::createFromJKU($this->get('jwks_uri'));
        }
        if ($this->has('client_secret')) {
            $key = new JWK([
                'kty' => 'oct',
                'use' => 'sig',
                'k' => $this->get('client_secret'),
            ]);
            if (null === $jwkset) {
                $jwk_set = new JWKSet();
                $jwk_set->addKey($key);

                return $jwk_set;
            } else {
                $jwkset->addKey($key);
            }
        }

        return $jwkset;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicId(): ResourceOwnerId
    {
        Assertion::notNull($this->clientId, 'Client not initialized.');

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
        Assertion::true($this->has($key), sprintf('Configuration value with key \'%s\' does not exist.', $key));

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
            '$schema' => $this->getSchema(),
            'type' => get_class($this),
            'client_id' => $this->getPublicId()->getValue(),
            'owner_id' => $this->getOwnerId()->getValue(),
            'parameters' => (object) $this->all(),
            'is_deleted' => $this->isDeleted(),
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $clientId = ClientId::create($json->client_id);
        $ownerId = null !== $json->owner_id ? UserAccountId::create($json->owner_id) : null;
        $parameters = DataBag::createFromArray((array) $json->parameters);
        $deleted = $json->is_deleted;

        $client = new self();
        $client->clientId = $clientId;
        $client->ownerId = $ownerId;
        $client->parameters = $parameters;
        $client->deleted = $deleted;

        return $client;
    }

    /**
     * @param Event $event
     *
     * @return Client
     */
    public function apply(Event $event): Client
    {
        $map = $this->getEventMap();
        Assertion::keyExists($map, $event->getType(), 'Unsupported event.');
        if (null !== $this->clientId) {
            Assertion::eq($this->clientId, $event->getDomainId(), 'Event not applicable for this client.');
        }
        $method = $map[$event->getType()];

        return $this->$method($event);
    }

    /**
     * @return array
     */
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
     * @param ClientEvent\ClientCreatedEvent $event
     *
     * @return Client
     */
    protected function applyClientCreatedEvent(ClientEvent\ClientCreatedEvent $event): Client
    {
        $clone = clone $this;
        $clone->clientId = $event->getClientId();
        $clone->ownerId = $event->getOwnerId();
        $clone->parameters = $event->getParameters();

        return $clone;
    }

    /**
     * @param ClientEvent\ClientOwnerChangedEvent $event
     *
     * @return Client
     */
    protected function applyClientOwnerChangedEvent(ClientEvent\ClientOwnerChangedEvent $event): Client
    {
        $clone = clone $this;
        $clone->ownerId = $event->getNewOwnerId();

        return $clone;
    }

    /**
     * @param ClientEvent\ClientDeletedEvent $event
     *
     * @return Client
     */
    protected function applyClientDeletedEvent(ClientEvent\ClientDeletedEvent $event): Client
    {
        $clone = clone $this;
        $clone->deleted = true;

        return $clone;
    }

    /**
     * @param ClientEvent\ClientParametersUpdatedEvent $event
     *
     * @return Client
     */
    protected function applyClientParametersUpdatedEvent(ClientEvent\ClientParametersUpdatedEvent $event): Client
    {
        $clone = clone $this;
        $clone->parameters = $event->getParameters();

        return $clone;
    }
}
