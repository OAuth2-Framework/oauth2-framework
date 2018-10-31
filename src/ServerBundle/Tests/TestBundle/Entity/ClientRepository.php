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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Persistence\ManagerRegistry;
use OAuth2Framework\Component\Core\Client\Client as ClientInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository as ClientRepositoryInterface;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

final class ClientRepository implements ClientRepositoryInterface, ServiceEntityRepositoryInterface
{
    private $entityRepository;
    private $entityManager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->entityManager = $managerRegistry->getManagerForClass(Client::class);
        $this->entityRepository = $this->entityManager->getRepository(Client::class);
    }

    public function find(ClientId $clientId): ?ClientInterface
    {
        return $this->entityRepository->find($clientId);
    }

    public function save(ClientInterface $client)
    {
        if (!$client instanceof Client) {
            throw new \InvalidArgumentException('Unsupported client class');
        }
        $this->entityManager->persist($client);
        $this->entityManager->flush();
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
