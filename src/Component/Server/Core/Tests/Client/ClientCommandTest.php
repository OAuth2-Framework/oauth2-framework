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

namespace OAuth2Framework\Component\Server\Core\Tests\Client;

use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Client\Command\ChangeOwnerCommand;
use OAuth2Framework\Component\Server\Core\Client\Command\ChangeOwnerCommandHandler;
use OAuth2Framework\Component\Server\Core\Client\Command\CreateClientCommand;
use OAuth2Framework\Component\Server\Core\Client\Command\CreateClientCommandHandler;
use OAuth2Framework\Component\Server\Core\Client\Command\DeleteClientCommand;
use OAuth2Framework\Component\Server\Core\Client\Command\DeleteClientCommandHandler;
use OAuth2Framework\Component\Server\Core\Client\Command\UpdateClientCommand;
use OAuth2Framework\Component\Server\Core\Client\Command\UpdateClientCommandHandler;
use OAuth2Framework\Component\Server\Core\Client\Rule\RuleManager;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group ClientCommand
 */
final class ClientCommandTest extends TestCase
{
    /**
     * @test
     */
    public function anClientCanBeCreatedUsingTheCommand()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $command = CreateClientCommand::create(
            $clientId,
            UserAccountId::create('USER_ACCOUNT_ID'),
            DataBag::create([])
        );
        $ruleManager = new RuleManager();

        $repository = $this->prophesize(ClientRepository::class);
        $repository->find($clientId)->willReturn(null)->shouldBeCalled();
        $repository->save(Argument::type(Client::class))->shouldBeCalled();

        $handler = new CreateClientCommandHandler(
            $repository->reveal(),
            $ruleManager
        );
        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anClientCanBeUpdatedUsingTheCommand()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $client = Client::createEmpty();
        $client = $client->create(
            $clientId,
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $command = UpdateClientCommand::create(
            $clientId,
            DataBag::create([])
        );
        $ruleManager = new RuleManager();

        $repository = $this->prophesize(ClientRepository::class);
        $repository->find($clientId)->willReturn($client)->shouldBeCalled();
        $repository->save(Argument::type(Client::class))->shouldBeCalled();

        $handler = new UpdateClientCommandHandler(
            $repository->reveal(),
            $ruleManager
        );
        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anClientOwnerCanBeChangedUsingTheCommand()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $client = Client::createEmpty();
        $client = $client->create(
            $clientId,
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $command = ChangeOwnerCommand::create(
            $clientId,
            UserAccountId::create('NEW_USER_ACCOUNT')
        );

        $repository = $this->prophesize(ClientRepository::class);
        $repository->find($clientId)->willReturn($client)->shouldBeCalled();
        $repository->save(Argument::type(Client::class))->shouldBeCalled();

        $handler = new ChangeOwnerCommandHandler(
            $repository->reveal()
        );
        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anClientCanBeDeletedUsingTheCommand()
    {
        $clientId = ClientId::create('CLIENT_ID');
        $client = Client::createEmpty();
        $client = $client->create(
            $clientId,
            DataBag::create([]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $command = DeleteClientCommand::create(
            $clientId
        );

        $repository = $this->prophesize(ClientRepository::class);
        $repository->find($clientId)->willReturn($client)->shouldBeCalled();
        $repository->save(Argument::type(Client::class))->shouldBeCalled();

        $handler = new DeleteClientCommandHandler(
            $repository->reveal()
        );
        $handler->handle($command);
    }
}
