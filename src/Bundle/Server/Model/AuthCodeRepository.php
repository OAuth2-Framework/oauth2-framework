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

use OAuth2Framework\Component\Server\Model\AuthCode\AuthCode;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeId;
use OAuth2Framework\Component\Server\Model\AuthCode\AuthCodeRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Recorder\RecordsMessages;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * AuthCodeRepository constructor.
     *
     * @param int                 $minLength
     * @param int                 $maxLength
     * @param int                 $lifetime
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     */
    public function __construct(int $minLength, int $maxLength, int $lifetime, EventStoreInterface $eventStore, RecordsMessages $eventRecorder)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->lifetime = $lifetime;
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ?ResourceServerId $resourceServerId, ?\DateTimeImmutable $expiresAt): AuthCode
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
        }

        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create(
            AuthCodeId::create(Uuid::uuid4()->toString()),
            $clientId,
            $userAccountId,
            $queryParameters,
            $redirectUri,
            $expiresAt,
            $parameters,
            $metadatas,
            $scopes,
            $withRefreshToken,
            $resourceServerId
        );

        return $authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AuthCodeId $authCodeId): ?AuthCode
    {
        $authCode = $this->getFromCache($authCodeId);
        if (null === $authCode) {
            $events = $this->eventStore->getEvents($authCodeId);
            if (!empty($events)) {
                $authCode = AuthCode::createEmpty();
                foreach ($events as $event) {
                    $authCode = $authCode->apply($event);
                }

                $this->cacheObject($authCode);
            }
        }

        return $authCode;
    }

    /**
     * @param AuthCode $authCode
     */
    public function save(AuthCode $authCode)
    {
        $events = $authCode->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $authCode->eraseMessages();
        $this->cacheObject($authCode);
    }

    /**
     * @param AdapterInterface $cache
     */
    public function enableDomainObjectCaching(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param AuthCodeId $authCodeId
     *
     * @return AuthCode|null
     */
    private function getFromCache(AuthCodeId $authCodeId): ?AuthCode
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCodeId->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            if ($item->isHit()) {
                return $item->get();
            }
        }

        return null;
    }

    /**
     * @param AuthCode $authCode
     */
    private function cacheObject(AuthCode $authCode)
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCode->getTokenId()->getValue());
        if (null !== $this->cache) {
            $item = $this->cache->getItem($itemKey);
            $item->set($authCode);
            $item->tag(['oauth2_server', 'auth_code', $itemKey]);
            $this->cache->save($item);
        }
    }
}
