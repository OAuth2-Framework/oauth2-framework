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

use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class PreConfiguredAuthorizationRevokedEvent extends Event
{
    /**
     * @var PreConfiguredAuthorizationId
     */
    private $preConfiguredAuthorizationId;

    /**
     * PreConfiguredAuthorizationRevokedEvent constructor.
     *
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param \DateTimeImmutable|null      $recordedOn
     * @param null|EventId                 $eventId
     */
    protected function __construct(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, ?\DateTimeImmutable $recordedOn, ?EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->preConfiguredAuthorizationId = $preConfiguredAuthorizationId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/pre-configured-authorization/revoked/1.0/schema';
    }

    /**
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     *
     * @return PreConfiguredAuthorizationRevokedEvent
     */
    public static function create(PreConfiguredAuthorizationId $preConfiguredAuthorizationId): PreConfiguredAuthorizationRevokedEvent
    {
        return new self($preConfiguredAuthorizationId, null, null);
    }

    /**
     * @return PreConfiguredAuthorizationId
     */
    public function getPreConfiguredAuthorizationId(): PreConfiguredAuthorizationId
    {
        return $this->preConfiguredAuthorizationId;
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
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $preConfiguredAuthorizationId = PreConfiguredAuthorizationId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        return new self($preConfiguredAuthorizationId, $recordedOn, $eventId);
    }
}
