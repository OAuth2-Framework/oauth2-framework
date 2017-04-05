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
use OAuth2Framework\Component\Server\Model\Client\Rule\RuleManager;

final class UpdateClientCommandHandler
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
     * UpdateClientCommandHandler constructor.
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
     * @param UpdateClientCommand $command
     */
    public function handle(UpdateClientCommand $command)
    {
        $parameters = $command->getParameters();
        $client = $command->getClient();
        $userAccountId = $client->getOwnerId();
        $validatedParameters = $this->ruleManager->handle($parameters, $userAccountId);
        $validatedParameters = $validatedParameters->with('client_id', $client->getPublicId());
        if (true === $client->has('client_id_issued_at')) {
            $validatedParameters = $validatedParameters->with('client_id_issued_at', $client->get('client_id_issued_at'));
        }
        $client = $client->withParameters($validatedParameters);
        $this->clientRepository->save($client);
        if (null !== $command->getDataTransporter()) {
            $callback = $command->getDataTransporter();
            $callback($client);
        }
    }
}
