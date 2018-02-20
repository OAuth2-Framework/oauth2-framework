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

namespace OAuth2Framework\Component\Server\Command\Client;

use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\Client\Rule\RuleManager;

final class CreateClientCommandHandler
{
    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    /**
     * CreateClientCommandHandler constructor.
     *
     * @param ClientRepositoryInterface $clientRepository
     * @param RuleManager               $ruleManager
     */
    public function __construct(ClientRepositoryInterface $clientRepository, RuleManager $ruleManager)
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
        $parameters = $command->getParameters();
        $parameters = $parameters->with('client_id', $clientId->getValue());
        $userAccountId = $command->getUserAccountId();
        $validatedParameters = $this->ruleManager->handle($parameters, $userAccountId);
        $client = Client::createEmpty();
        $client = $client->create($clientId, $validatedParameters, $userAccountId);
        $this->clientRepository->save($client);
        if (null !== $command->getDataTransporter()) {
            $callback = $command->getDataTransporter();
            $callback($client);
        }
    }
}
