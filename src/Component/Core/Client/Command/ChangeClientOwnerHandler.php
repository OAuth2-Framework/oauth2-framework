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
use OAuth2Framework\Component\Core\Client\Event\ClientOwnerChangedEvent;
use SimpleBus\SymfonyBridge\Bus\EventBus;

class ChangeClientOwnerHandler
{
    private $clientRepository;
    private $eventBus;

    public function __construct(ClientRepository $clientRepository, EventBus $eventBus)
    {
        $this->clientRepository = $clientRepository;
        $this->eventBus = $eventBus;
    }

    public function handle(ChangeClientOwner $command): void
    {
        $client = $this->clientRepository->find($command->getClientId());
        if (!$client) {
            throw new \InvalidArgumentException(\sprintf('The client with ID "%s" does not exist.', $command->getClientId()->getValue()));
        }

        $client->setOwnerId(
            $command->getOwnerId()
        );
        $this->clientRepository->save($client);
        $event = new ClientOwnerChangedEvent(
            $command->getClientId(),
            $command->getOwnerId()
        );
        $this->eventBus->handle($event);
    }
}
