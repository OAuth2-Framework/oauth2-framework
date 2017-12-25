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

namespace OAuth2Framework\Component\Server\AuthorizationCodeGrant\Event;

use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Server\Core\DomainObject;
use OAuth2Framework\Component\Server\Core\Event\Event;
use OAuth2Framework\Component\Server\Core\Event\EventId;
use OAuth2Framework\Component\Server\Core\Id\Id;

final class AuthorizationCodeRevokedEvent extends Event
{
    /**
     * @var AuthorizationCodeId
     */
    private $authorizationCodeId;

    /**
     * AuthorizationCodeRevokedEvent constructor.
     *
     * @param AuthorizationCodeId              $authorizationCodeId
     * @param \DateTimeImmutable|null $recordedOn
     * @param EventId|null            $eventId
     */
    protected function __construct(AuthorizationCodeId $authorizationCodeId, ? \DateTimeImmutable $recordedOn, ? EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->authorizationCodeId = $authorizationCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/revoked/1.0/schema';
    }

    /**
     * @param AuthorizationCodeId $authorizationCodeId
     *
     * @return AuthorizationCodeRevokedEvent
     */
    public static function create(AuthorizationCodeId $authorizationCodeId): AuthorizationCodeRevokedEvent
    {
        return new self($authorizationCodeId, null, null);
    }

    /**
     * @return AuthorizationCodeId
     */
    public function getAuthorizationCodeId(): AuthorizationCodeId
    {
        return $this->authorizationCodeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAuthorizationCodeId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObject
    {
        $authorizationCodeId = AuthorizationCodeId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        return new self($authorizationCodeId, $recordedOn, $eventId);
    }
}
