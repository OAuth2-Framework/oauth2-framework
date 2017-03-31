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

namespace OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Event\PreConfiguredAuthorization as PreConfiguredAuthorizationEvent;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;
use SimpleBus\Message\Recorder\ContainsRecordedMessages;
use SimpleBus\Message\Recorder\PrivateMessageRecorderCapabilities;

final class PreConfiguredAuthorization implements ContainsRecordedMessages, DomainObjectInterface
{
    use PrivateMessageRecorderCapabilities;

    /**
     * @var PreConfiguredAuthorizationId|null
     */
    private $preConfiguredAuthorizationId = null;

    /**
     * @var UserAccountId|null
     */
    private $userAccountId = null;

    /**
     * @var ClientId|null
     */
    private $clientId = null;

    /**
     * @var string|null
     */
    private $scopes = null;

    /**
     * @var bool
     */
    private $revoked = false;

    /**
     * PreConfiguredAuthorization constructor.
     *
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param UserAccountId                $userAccountId
     * @param ClientId                     $clientId
     * @param array                        $scopes
     *
     * @return PreConfiguredAuthorization
     */
    public function create(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, UserAccountId $userAccountId, ClientId $clientId, array $scopes): PreConfiguredAuthorization
    {
        $clone = clone $this;
        $clone->preConfiguredAuthorizationId = $preConfiguredAuthorizationId;
        $clone->userAccountId = $userAccountId;
        $clone->clientId = $clientId;
        $clone->scopes = implode(' ', $scopes);

        $event = PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationCreatedEvent::create($preConfiguredAuthorizationId, $clientId, $userAccountId, $scopes);
        $clone->record($event);

        return $clone;
    }

    /**
     * @return PreConfiguredAuthorization
     */
    public static function createEmpty(): PreConfiguredAuthorization
    {
        return new self();
    }

    /**
     * @return PreConfiguredAuthorizationId
     */
    public function getPreConfiguredAuthorizationId(): PreConfiguredAuthorizationId
    {
        return $this->preConfiguredAuthorizationId;
    }

    /**
     * @return UserAccountId
     */
    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    /**
     * @return ClientId
     */
    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return explode(' ', $this->scopes);
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @return PreConfiguredAuthorization
     */
    public function markAsRevoked(): PreConfiguredAuthorization
    {
        $clone = clone $this;
        $clone->revoked = true;
        $event = PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationRevokedEvent::create($clone->getPreConfiguredAuthorizationId());
        $clone->record($event);

        return $clone;
    }

    /**
     * @param Event $event
     *
     * @return PreConfiguredAuthorization
     */
    public function apply(Event $event): PreConfiguredAuthorization
    {
        $map = $this->getEventMap();
        Assertion::keyExists($map, $event->getType(), 'Unsupported event.');
        if (null !== $this->preConfiguredAuthorizationId) {
            Assertion::eq($this->preConfiguredAuthorizationId, $event->getDomainId(), 'Event not applicable for this initial access token.');
        }
        $method = $map[$event->getType()];

        return $this->$method($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/model/pre-configured-authorization/1.0/schema';
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $preConfiguredAuthorizationId = PreConfiguredAuthorizationId::create($json->pre_configured_authorization_id);
        $userAccountId = UserAccountId::create($json->user_account_id);
        $clientId = ClientId::create($json->client_id);
        $scopes = implode(' ', (array) $json->scopes);
        $revoked = $json->is_revoked;

        $preConfiguredAuthorization = new self();
        $preConfiguredAuthorization->preConfiguredAuthorizationId = $preConfiguredAuthorizationId;
        $preConfiguredAuthorization->userAccountId = $userAccountId;
        $preConfiguredAuthorization->clientId = $clientId;
        $preConfiguredAuthorization->scopes = $scopes;
        $preConfiguredAuthorization->revoked = $revoked;

        return $preConfiguredAuthorization;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
                '$schema' => $this->getSchema(),
                'type' => get_class($this),
                'pre_configured_authorization_id' => $this->getPreConfiguredAuthorizationId()->getValue(),
                'user_account_id' => $this->getUserAccountId()->getValue(),
                'client_id' => $this->getClientId()->getValue(),
                'scopes' => $this->getScopes(),
                'is_revoked' => $this->isRevoked(),
            ];

        return $data;
    }

    /**
     * @return array
     */
    private function getEventMap(): array
    {
        return [
            PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationCreatedEvent::class => 'applyPreConfiguredAuthorizationCreatedEvent',
            PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationRevokedEvent::class => 'applyPreConfiguredAuthorizationRevokedEvent',
        ];
    }

    /**
     * @param PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationCreatedEvent $event
     *
     * @return PreConfiguredAuthorization
     */
    protected function applyPreConfiguredAuthorizationCreatedEvent(PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationCreatedEvent $event): PreConfiguredAuthorization
    {
        $clone = clone $this;
        $clone->preConfiguredAuthorizationId = $event->getPreConfiguredAuthorizationId();
        $clone->userAccountId = $event->getUserAccountId();
        $clone->clientId = $event->getClientId();
        $clone->scopes = implode(' ', $event->getScopes());

        return $clone;
    }

    /**
     * @param PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationRevokedEvent $event
     *
     * @return PreConfiguredAuthorization
     */
    protected function applyPreConfiguredAuthorizationRevokedEvent(PreConfiguredAuthorizationEvent\PreConfiguredAuthorizationRevokedEvent $event): PreConfiguredAuthorization
    {
        $clone = clone $this;
        $clone->revoked = true;

        return $clone;
    }
}
