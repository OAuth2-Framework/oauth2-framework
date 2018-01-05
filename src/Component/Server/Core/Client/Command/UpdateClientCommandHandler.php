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

namespace OAuth2Framework\Component\Server\Core\Client\Command;

use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Client\Rule\RuleManager;

final class UpdateClientCommandHandler
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
     * UpdateClientCommandHandler constructor.
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
     * @param UpdateClientCommand $command
     */
    public function handle(UpdateClientCommand $command)
    {
        $client = $this->clientRepository->find($command->getClientId());
        if (null === $client) {
            throw new \InvalidArgumentException(sprintf('The client with ID "%s" does not exists.', $command->getClientId()->getValue()));
        }
        $parameters = $command->getParameters();
        $userAccountId = $client->getOwnerId();
        $validatedParameters = $this->ruleManager->handle($client->getPublicId(), $parameters, $userAccountId);
        $client = $client->withParameters($validatedParameters);
        $this->clientRepository->save($client);
    }
}
