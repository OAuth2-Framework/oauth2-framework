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

namespace OAuth2Framework\Component\Core\Client\Event;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainObject;
use OAuth2Framework\Component\Core\Event\Event;
use OAuth2Framework\Component\Core\Id\Id;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class ClientCreatedEvent extends Event
{
    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var DataBag
     */
    private $parameters;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId;

    /**
     * ClientCreatedEvent constructor.
     */
    protected function __construct(ClientId $clientId, DataBag $parameters, ?UserAccountId $userAccountId)
    {
        $this->clientId = $clientId;
        $this->parameters = $parameters;
        $this->userAccountId = $userAccountId;
    }

    /**
     * @return ClientCreatedEvent
     */
    public static function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $userAccountId): self
    {
        return new self($clientId, $parameters, $userAccountId);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $clientId = new ClientId($json->domain_id);
        $userAccountId = null === $json->payload->user_account_id ? null : new UserAccountId($json->payload->user_account_id);
        $parameters = new DataBag((array) $json->payload->parameters);

        return new self(
            $clientId,
            $parameters,
            $userAccountId
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema';
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'user_account_id' => $this->userAccountId ? $this->userAccountId->getValue() : null,
            'parameters' => (object) $this->parameters->all(),
        ];
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getParameters(): DataBag
    {
        return $this->parameters;
    }

    /**
     * @return null|UserAccountId
     */
    public function getOwnerId(): ?UserAccountId
    {
        return $this->userAccountId;
    }
}
