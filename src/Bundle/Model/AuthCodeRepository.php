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

namespace OAuth2Framework\Bundle\Model;

use OAuth2Framework\Bundle\Service\RandomIdGenerator;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeRepository;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Cache\Adapter\AdapterInterface;

final class AuthCodeRepository implements AuthorizationCodeRepository
{
    /**
     * @var int
     */
    private $minLength;

    /**
     * @var int
     */
    private $maxLength;

    /**
     * @var int
     */
    private $lifetime;

    /**
     * @var MessageBus
     */
    private $eventBus;

    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * AuthCodeRepository constructor.
     *
     * @param int                 $minLength
     * @param int                 $maxLength
     * @param int                 $lifetime
     * @param EventStoreInterface $eventStore
     * @param MessageBus          $eventBus
     * @param AdapterInterface    $cache
     */
    public function __construct(int $minLength, int $maxLength, int $lifetime, EventStoreInterface $eventStore, MessageBus $eventBus, AdapterInterface $cache)
    {
        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->lifetime = $lifetime;
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ClientId $clientId, UserAccountId $userAccountId, array $queryParameters, string $redirectUri, DataBag $parameters, DataBag $metadatas, array $scopes, bool $withRefreshToken, ? ResourceServerId $resourceServerId, ? \DateTimeImmutable $expiresAt): AuthCode
    {
        if (null === $expiresAt) {
            $expiresAt = new \DateTimeImmutable(sprintf('now +%u seconds', $this->lifetime));
        }

        $authCodeId = AuthCodeId::create(RandomIdGenerator::generate($this->minLength, $this->maxLength));
        $authCode = AuthCode::createEmpty();
        $authCode = $authCode->create($authCodeId, $clientId, $userAccountId, $queryParameters, $redirectUri, $expiresAt, $parameters, $metadatas, $scopes, $withRefreshToken, $resourceServerId);

        return $authCode;
    }

    /**
     * {@inheritdoc}
     */
    public function find(AuthCodeId $authCodeId): ? AuthCode
    {
        $authCode = $this->getFromCache($authCodeId);
        if (null === $authCode) {
            $events = $this->eventStore->getEvents($authCodeId);
            if (!empty($events)) {
                $authCode = $this->getFromEvents($events);
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
            $this->eventBus->handle($event);
        }
        $authCode->eraseMessages();
        $this->cacheObject($authCode);
    }

    /**
     * @param Event[] $events
     *
     * @return AuthCode
     */
    private function getFromEvents(array $events): AuthCode
    {
        $authCode = AuthCode::createEmpty();
        foreach ($events as $event) {
            $authCode = $authCode->apply($event);
        }

        return $authCode;
    }

    /**
     * @param AuthCodeId $authCodeId
     *
     * @return AuthCode|null
     */
    private function getFromCache(AuthCodeId $authCodeId): ? AuthCode
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCodeId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param AuthCode $authCode
     */
    private function cacheObject(AuthCode $authCode)
    {
        $itemKey = sprintf('oauth2-auth_code-%s', $authCode->getTokenId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($authCode);
        $item->tag(['oauth2_server', 'auth_code', $itemKey]);
        $this->cache->save($item);
    }
}
