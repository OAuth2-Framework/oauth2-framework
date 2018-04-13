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
use OAuth2Framework\Component\RefreshTokenGrant\Command;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenId;
use OAuth2Framework\Component\RefreshTokenGrant\RefreshTokenRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group TypeHint
 * @group RefreshTokenCommand
 * @group RevokeRefreshTokenCommand
 */
class RevokeRefreshTokenCommandTest extends TestCase
{
    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to find the refresh token with ID "REFRESH_TOKEN_ID".
     */
    public function aCommandIsCalledButTheRefreshTokenDoesNotExist()
    {
        $repository = $this->prophesize(RefreshTokenRepository::class);
        $repository->find(RefreshTokenId::create('REFRESH_TOKEN_ID'))->shouldBeCalled()->willReturn(null);
        $repository->save(Argument::type(RefreshToken::class))->shouldNotBeCalled();

        $command = Command\RevokeRefreshTokenCommand::create(
            RefreshTokenId::create('REFRESH_TOKEN_ID')
        );

        $handler = new Command\RevokeRefreshTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }

    /**
     * @test
     */
    public function aCommandIsCalledAndTheRefreshTokenIsRevoked()
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
        $repository->save(Argument::type(RefreshToken::class))->shouldBeCalled();

        $command = Command\RevokeRefreshTokenCommand::create(
            RefreshTokenId::create('REFRESH_TOKEN_ID')
        );

        $handler = new Command\RevokeRefreshTokenCommandHandler($repository->reveal());

        $handler->handle($command);
    }
}
