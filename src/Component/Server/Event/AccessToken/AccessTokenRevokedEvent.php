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

namespace OAuth2Framework\Component\Server\Event\AccessToken;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Model\Event\Event;
use OAuth2Framework\Component\Server\Model\Event\EventId;
use OAuth2Framework\Component\Server\Model\Id\Id;
use OAuth2Framework\Component\Server\Schema\DomainObjectInterface;

final class AccessTokenRevokedEvent extends Event
{
    /**
     * @var AccessTokenId
     */
    private $accessTokenId;

    /**
     * AccessTokenRevokedEvent constructor.
     *
     * @param AccessTokenId           $accessTokenId
     * @param \DateTimeImmutable|null $recordedOn
     * @param null|EventId            $eventId
     */
    protected function __construct(AccessTokenId $accessTokenId, ?\DateTimeImmutable $recordedOn, ?EventId $eventId)
    {
        parent::__construct($recordedOn, $eventId);
        $this->accessTokenId = $accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string
    {
        return 'https://oauth2-framework.spomky-labs.com/schemas/events/access-token/revoked/1.0/schema';
    }

    /**
     * @param AccessTokenId $accessTokenId
     *
     * @return AccessTokenRevokedEvent
     */
    public static function create(AccessTokenId $accessTokenId): AccessTokenRevokedEvent
    {
        return new self($accessTokenId, null, null);
    }

    /**
     * {@inheritdoc}
     */
    public static function createFromJson(\stdClass $json): DomainObjectInterface
    {
        $accessTokenId = AccessTokenId::create($json->domain_id);
        $eventId = EventId::create($json->event_id);
        $recordedOn = \DateTimeImmutable::createFromFormat('U', (string) $json->recorded_on);

        return new self($accessTokenId, $recordedOn, $eventId);
    }

    /**
     * @return AccessTokenId
     */
    public function getAccessTokenId(): AccessTokenId
    {
        return $this->accessTokenId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainId(): Id
    {
        return $this->getAccessTokenId();
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload()
    {
    }
}
