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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\Command;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group RefreshTokenCommand
 * @group AddAccessTokenCommand
 */
final class AddAccessTokenCommandTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to find the refresh token with ID "UNKNOWN_REFRESH_TOKEN_ID".
     */
    public function aCommandIsCalledButTheRefreshTokenDoesNotExist()
    {
        $repository = $this->prophesize(RefreshTokenRepository::class);
        $repository->find(RefreshTokenId::create('UNKNOWN_REFRESH_TOKEN_ID'))->shouldBeCalled()->willReturn(null);
        $repository->save(Argument::type(RefreshToken::class))->shouldNotBeCalled();

        $command = Command\AddAccessTokenCommand::create(
            RefreshTokenId::create('UNKNOWN_REFRESH_TOKEN_ID'),
            AccessTokenId::create('ACCESS_TOKEN_ID')
        );

        $handler = new Command\AddAccessTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }

    /**
     * @test
     */
    public function aCommandIsCalledAndTheRefreshTokenIsModified()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            DataBag::create([]),
            ['scope1', 'scope2'],
            new \DateTimeImmutable('now +1 day'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $refreshToken->eraseMessages();

        $repository = $this->prophesize(RefreshTokenRepository::class);
        $repository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->shouldBeCalled()->willReturn($refreshToken);
        $repository->save(Argument::type(RefreshToken::class))->shouldBeCalled();

        $command = Command\AddAccessTokenCommand::create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            AccessTokenId::create('ACCESS_TOKEN_ID')
        );

        $handler = new Command\AddAccessTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }
}
