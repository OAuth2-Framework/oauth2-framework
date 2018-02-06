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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests;

use OAuth2Framework\Component\ClientRegistrationEndpoint\Command\CreateInitialAccessTokenCommand;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Command\CreateInitialAccessTokenCommandHandler;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Command\RevokeInitialAccessTokenCommand;
use OAuth2Framework\Component\ClientRegistrationEndpoint\Command\RevokeInitialAccessTokenCommandHandler;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenRepository;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group InitialAccessTokenCommand
 */
class CommandTest extends TestCase
{
    /**
     * @test
     */
    public function anInitialAccessTokenCanBeCreated()
    {
        $initialAccessTokenRepository = $this->prophesize(InitialAccessTokenRepository::class);
        $initialAccessTokenRepository->save(Argument::type(InitialAccessToken::class))->shouldBeCalledTimes(1);

        $initialAccessTokenCreationCommandHandler = new CreateInitialAccessTokenCommandHandler($initialAccessTokenRepository->reveal());

        $command = CreateInitialAccessTokenCommand::create(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1 year')
        );

        $initialAccessTokenCreationCommandHandler->handle($command);
    }

    /**
     * @test
     */
    public function anInitialAccessTokenCanBeRevoked()
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1 year')
        );
        $initialAccessToken->eraseMessages();
        $initialAccessTokenRepository = $this->prophesize(InitialAccessTokenRepository::class);
        $initialAccessTokenRepository->find(InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'))
            ->shouldBeCalledTimes(1)
            ->willReturn($initialAccessToken);
        $initialAccessTokenRepository->save(Argument::type(InitialAccessToken::class))->shouldBeCalledTimes(1);

        $initialAccessTokenCreationCommandHandler = new RevokeInitialAccessTokenCommandHandler($initialAccessTokenRepository->reveal());

        $command = RevokeInitialAccessTokenCommand::create(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID')
        );

        $initialAccessTokenCreationCommandHandler->handle($command);
    }
}
