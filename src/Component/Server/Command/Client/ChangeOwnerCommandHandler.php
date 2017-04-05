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

namespace OAuth2Framework\Component\Server\Command\Client;

use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;

final class ChangeOwnerCommandHandler
{
    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * ChangeOwnerCommandHandler constructor.
     *
     * @param ClientRepositoryInterface $clientRepository
     */
    public function __construct(ClientRepositoryInterface $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    /**
     * @param ChangeOwnerCommand $command
     */
    public function handle(ChangeOwnerCommand $command)
    {
        $owner = $command->getNewOwnerId();
        $client = $command->getClient();
        $client = $client->withOwnerId($owner);
        $this->clientRepository->save($client);
    }
}
