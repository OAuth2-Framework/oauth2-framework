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

namespace OAuth2Framework\Bundle\Server\Model;

use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorization;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationId;
use OAuth2Framework\Component\Server\Model\PreConfiguredAuthorization\PreConfiguredAuthorizationRepositoryInterface;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class PreConfiguredAuthorizationRepository implements PreConfiguredAuthorizationRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var RecordsMessages
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
    public function find(UserAccountId $userAccountId, ClientId $clientId, array $scopes, ?ResourceServerId $resourceServerId): ?PreConfiguredAuthorization
    {
        $hash = $this->calculateHash($userAccountId, $clientId, $scopes, $resourceServerId);
        $preConfiguredAuthorization = $this->getFromCache($hash);
        if (null === $preConfiguredAuthorization) {
            $events = $this->eventStore->getEvents($hash);
            if (!empty($events)) {
                $preConfiguredAuthorization = PreConfiguredAuthorization::createEmpty();
                foreach ($events as $event) {
                    $preConfiguredAuthorization = $preConfiguredAuthorization->apply($event);
                }

                $this->cacheObject($preConfiguredAuthorization);
            }
        }

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
        $this->cacheObject($preConfiguredAuthorization);
    }

    /**
     * @param AdapterInterface $cache
     */
    public function enableDomainObjectCaching(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param PreConfiguredAuthorizationId $preConfiguredAuthorizationId
     *
     * @return PreConfiguredAuthorization|null
     */
    private function getFromCache(PreConfiguredAuthorizationId $preConfiguredAuthorizationId): ?PreConfiguredAuthorization
    {
        $itemKey = sprintf('oauth2-pre_configured_authorization-%s', $preConfiguredAuthorizationId->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        return null;
    }

    /**
     * @param PreConfiguredAuthorization $preConfiguredAuthorization
     */
    private function cacheObject(PreConfiguredAuthorization $preConfiguredAuthorization)
    {
        $itemKey = sprintf('oauth2-pre_configured_authorization-%s', $preConfiguredAuthorization->getPreConfiguredAuthorizationId()->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            $item->set($preConfiguredAuthorization);
            $item->tag(['oauth2_server', 'pre_configured_authorization', $itemKey]);
            $this->cache->save($item);
        }
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
}
