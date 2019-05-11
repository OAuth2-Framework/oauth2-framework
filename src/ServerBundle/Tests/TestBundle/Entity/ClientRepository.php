<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Assert\Assertion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use OAuth2Framework\Component\Core\Client\Client as ClientInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository as ClientRepositoryInterface;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Psr\Cache\CacheItemPoolInterface;

final class ClientRepository implements ClientRepositoryInterface, ServiceEntityRepositoryInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function find(ClientId $clientId): ?ClientInterface
    {
        $item = $this->cache->getItem('Client-'.$clientId->getValue());
        if ($item->isHit()) {
            return $item->get();
        }

        return null;
    }

    public function save(ClientInterface $client): void
    {
        Assertion::isInstanceOf($client, Client::class, 'Unsupported client class');
        $item = $this->cache->getItem('Client-'.$client->getClientId()->getValue());
        $item->set($client);
        $this->cache->save($item);
    }

    public function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): ClientInterface
    {
        return new Client($clientId, $parameters, $ownerId);
    }

    public function createClientId(): ClientId
    {
        return new ClientId(\bin2hex(random_bytes(32)));
    }
}
