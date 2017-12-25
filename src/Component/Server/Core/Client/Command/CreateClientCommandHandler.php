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

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Client\Rule\RuleManager;

final class CreateClientCommandHandler
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param ClientRepository $clientRepository
     * @param RuleManager      $ruleManager
     */
    public function __construct(ClientRepository $clientRepository, RuleManager $ruleManager)
    {
        $this->clientRepository = $clientRepository;
        $this->ruleManager = $ruleManager;
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
        $validatedParameters = $this->ruleManager->handle($clientId, $parameters, $userAccountId);
        $client = Client::createEmpty();
        $client = $client->create($clientId, $validatedParameters, $userAccountId);
        $this->clientRepository->save($client);
    }
}
