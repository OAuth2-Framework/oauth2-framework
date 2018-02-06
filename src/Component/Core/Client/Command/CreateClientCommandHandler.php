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

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;

class CreateClientCommandHandler
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param CreateClientCommand $command
     */
    public function handle(CreateClientCommand $command)
    {
        $clientId = $command->getClientId();
        $client = $this->clientRepository->find($clientId);
        if (null !== $client) {
            throw new \InvalidArgumentException(sprintf('The client with ID "%s" already exists.', $clientId->getValue()));
        }
        $parameters = $command->getParameters();
        $userAccountId = $command->getUserAccountId();
        $client = Client::createEmpty();
        $client = $client->create($clientId, $parameters, $userAccountId);
        $this->clientRepository->save($client);
    }
}
