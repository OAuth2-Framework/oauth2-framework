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

namespace OAuth2Framework\Component\Core\Client\Command;

use OAuth2Framework\Component\Core\Client\ClientRepository;

class ChangeOwnerCommandHandler
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * ChangeOwnerCommandHandler constructor.
     *
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param ChangeOwnerCommand $command
     */
    public function handle(ChangeOwnerCommand $command)
    {
        $client = $this->clientRepository->find($command->getClientId());
        if (null === $client) {
            throw new \InvalidArgumentException(sprintf('The client with ID "%s" does not exists.', $command->getClientId()->getValue()));
        }
        $owner = $command->getNewOwnerId();
        $client = $client->withOwnerId($owner);
        $this->clientRepository->save($client);
    }
}
