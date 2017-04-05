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

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
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
        $parameters = $command->getParameters();
        $userAccountId = $command->getUserAccountId();
        $validatedParameters = $this->ruleManager->handle($parameters, $userAccountId);
        Assertion::true($validatedParameters->has('client_id'), 'Client ID not in the parameters.');
        $clientId = $validatedParameters->get('client_id');
        Assertion::string($clientId, 'Invalid client ID parameter.');
        $client = Client::createEmpty();
        $client = $client->create(ClientId::create($clientId), $validatedParameters, $userAccountId);
        $this->clientRepository->save($client);
        if (null !== $command->getDataTransporter()) {
            $callback = $command->getDataTransporter();
            $callback($client);
        }
    }
}
