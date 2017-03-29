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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorization;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;

final class PreConfiguredAuthorizationRepository implements PreConfiguredAuthorizationRepositoryInterface
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var EventStoreInterface
     */
    private $eventRecorder;

    /**
     * PreConfiguredAuthorizationRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->createAndSavePreConfiguredAuthorization(
            $this->calculateHash(UserAccountId::create('john.1'), ClientId::create('client1'), ['openid', 'profile', 'phone', 'address', 'email'], null),
            UserAccountId::create('john.1'),
            ClientId::create('client1'),
            ['openid', 'profile', 'phone', 'address', 'email']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(UserAccountId $userAccountId, ClientId $clientId, array $scopes, ?ResourceServerId $resourceServerId): PreConfiguredAuthorization
    {
        $hash = $this->calculateHash($userAccountId, $clientId, $scopes, $resourceServerId);
        $preConfiguredAuthorization = PreConfiguredAuthorization::createEmpty();
        $preConfiguredAuthorization = $preConfiguredAuthorization->create($hash, $userAccountId, $clientId, $scopes);

        return $preConfiguredAuthorization;
    }

    /**
     * {@inheritdoc}
     */
    public function save(PreConfiguredAuthorization $preConfiguredAuthorization)
    {
        $events = $preConfiguredAuthorization->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $preConfiguredAuthorization->eraseMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function find(UserAccountId $userAccountId, ClientId $clientId, array $scopes, ?ResourceServerId $resourceServerId): ?PreConfiguredAuthorization
    {
        $hash = $this->calculateHash($userAccountId, $clientId, $scopes, $resourceServerId);
        $preConfiguredAuthorization = null;
        $events = $this->eventStore->getEvents($hash);
        if (!empty($events)) {
            $preConfiguredAuthorization = PreConfiguredAuthorization::createEmpty();
            foreach ($events as $event) {
                $preConfiguredAuthorization = $preConfiguredAuthorization->apply($event);
            }
        }

        return $preConfiguredAuthorization;
    }

    /**
     * @param ResourceOwnerId       $resourceOwnerId
     * @param ClientId              $clientId
     * @param \string[]             $scope
     * @param null|ResourceServerId $resourceServerId
     *
     * @return PreConfiguredAuthorizationId
     */
    private function calculateHash(ResourceOwnerId $resourceOwnerId, ClientId $clientId, array $scope, ?ResourceServerId $resourceServerId): PreConfiguredAuthorizationId
    {
        return PreConfiguredAuthorizationId::create(hash(
            'sha512',
            sprintf(
                '%s%s%s%s',
                $resourceOwnerId,
                $clientId,
                implode(' ', $scope),
                $resourceServerId ? $resourceServerId->getValue() : ''
            )
        ));
    }

    /**
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     * @param UserAccountId                $userAccountId
     * @param ClientId                     $clientId
     * @param array                        $scopes
     */
    private function createAndSavePreConfiguredAuthorization(PreConfiguredAuthorizationId $preConfiguredAuthorizationId, UserAccountId $userAccountId, ClientId $clientId, array $scopes)
    {
        $preConfiguredAuthorization = PreConfiguredAuthorization::createEmpty();
        $preConfiguredAuthorization = $preConfiguredAuthorization->create(
            $preConfiguredAuthorizationId,
            $userAccountId,
            $clientId,
            $scopes
        );
        $events = $preConfiguredAuthorization->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $preConfiguredAuthorization->eraseMessages();
        $this->save($preConfiguredAuthorization);
    }
}
