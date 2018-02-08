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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class ClientRepository implements \OAuth2Framework\Component\Core\Client\ClientRepository
{
    /**
     * @var AdapterInterface
     */
    private $cache;

    /**
     * ClientRepository constructor.
     *
     * @param AdapterInterface $cache
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function find(ClientId $clientId): ? Client
    {
        $client = $this->getFromCache($clientId);

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client)
    {
        $client->eraseMessages();
        $this->cacheObject($client);
    }

    /**
     * @param ClientId $clientId
     *
     * @return Client|null
     */
    private function getFromCache(ClientId $clientId): ? Client
    {
        $itemKey = sprintf('oauth2-client-%s', $clientId->getValue());
        $item = $this->cache->getItem($itemKey);
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    /**
     * @param Client $client
     */
    private function cacheObject(Client $client)
    {
        $itemKey = sprintf('oauth2-client-%s', $client->getPublicId()->getValue());
        $item = $this->cache->getItem($itemKey);
        $item->set($client);
        $item->tag(['oauth2_server', 'client', $itemKey]);
        $this->cache->save($item);
    }
}
