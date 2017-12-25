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

namespace OAuth2Framework\Component\Server\Core\Client\Command;

use OAuth2Framework\Component\Server\Core\Client\ClientRepository;

final class DeleteClientCommandHandler
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * DeleteClientCommandHandler constructor.
     *
     * @param ClientRepository $clientRepository
     */
    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param DeleteClientCommand $command
     */
    public function handle(DeleteClientCommand $command)
    {
        $clientId = $command->getClientId();
        $client = $this->clientRepository->find($clientId);
        if (null !== $client && !$client->isDeleted()) {
            $client = $client->markAsDeleted();
            $this->clientRepository->save($client);
        }
    }
}