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

namespace OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class PreConfiguredAuthorizationCreatedEvent extends Event
{
    /**
     * @var PreConfiguredAuthorizationId
     */
    private $preConfiguredAuthorizationId;

    /**
     * @var ClientId
     */
    private $clientId;

    /**
     * @var UserAccountId
     */
    private $userAccountId;

    /**
     * @var string[]
     */
    private $scopes;

    /**
     * PreConfiguredAuthorizationCreatedEvent constructor.
     *
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param ClientId                     $clientId
     * @param UserAccountId                $userAccountId
     * @param array                        $scopes
     * @param \DateTimeImmutable|null      $recordedOn
     * @param EventId|null                 $eventId
     */
    protected function __construct(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, ClientId $clientId, UserAccountId $userAccountId, array $scopes, ?\DateTimeImmutable $recordedOn, ?EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->preConfiguredAuthorizationId = $preConfiguredAuthorizationId;
        $this->clientId = $clientId;
        $this->userAccountId = $userAccountId;
        $this->scopes = $scopes;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/created/1.0/schema';
    }

    /**
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param ClientId                     $clientId
     * @param UserAccountId                $userAccountId
     * @param array                        $scopes
     *
     * @return PreConfiguredAuthorizationCreatedEvent
     */
    public static function create(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, ClientId $clientId, UserAccountId $userAccountId, array $scopes): PreConfiguredAuthorizationCreatedEvent
    {
        return new self($preConfiguredAuthorizationId, $clientId, $userAccountId, $scopes, null, null);
    }

    /**
     * @return PreConfiguredAuthorizationId
     */
    public function getPreConfiguredAuthorizationId(): PreConfiguredAuthorizationId
    {
        return $this->preConfiguredAuthorizationId;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return \string[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getPreConfiguredAuthorizationId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
        return (object) [
            'pre_configured_authorization_id' => $this->preConfiguredAuthorizationId->getValue(),
            'client_id' => $this->clientId->getValue(),
            'user_account_id' => $this->userAccountId->getValue(),
            'scopes' => $this->scopes,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $preConfiguredAuthorization = PreConfiguredAuthorizationId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        $clientId = ClientId::create($json->payload->client_id);
        $userAccountId = UserAccountId::create($json->payload->user_account_id);
        $scopes = (array) $json->payload->scopes;

        return new self($preConfiguredAuthorization, $clientId, $userAccountId, $scopes, $recordedOn, $eventId);
    }
}
