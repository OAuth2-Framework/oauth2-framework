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

namespace OAuth2Framework\Component\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Component\RefreshTokenGrant\Command;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group RefreshTokenCommand
 * @group CreateRefreshTokenCommand
 */
class CreateRefreshTokenCommandTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The refresh token with ID "REFRESH_TOKEN_ID" already exists.
     */
    public function aCommandIsCalledButTheRefreshTokenAlreadyExists()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'metadata' => 'foo',
                'scope' => 'scope1 scope2',
            ]),
            DataBag::create([
                'parameter1' => 'bar',
            ]),
            new \DateTimeImmutable('now +1 day'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $repository = $this->prophesize(RefreshTokenRepository::class);
        $repository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->shouldBeCalled()->willReturn($refreshToken);
        $repository->save(Argument::type(RefreshToken::class))->shouldNotBeCalled();

        $command = Command\CreateRefreshTokenCommand::create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1day'),
            DataBag::create([
                'scope' => ['scope'],
            ]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $handler = new Command\CreateRefreshTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }

    /**
     * @test
     */
    public function aCommandIsCalledAndTheRefreshTokenIsCreated()
    {
        $repository = $this->prophesize(RefreshTokenRepository::class);
        $repository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->shouldBeCalled()->willReturn(null);
        $repository->save(Argument::type(RefreshToken::class))->shouldBeCalled();

        $command = Command\CreateRefreshTokenCommand::create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1day'),
            DataBag::create([
                'scope' => ['scope'],
            ]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $handler = new Command\CreateRefreshTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }
}
