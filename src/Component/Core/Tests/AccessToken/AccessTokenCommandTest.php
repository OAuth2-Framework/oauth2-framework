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

namespace OAuth2Framework\Component\Core\Tests\AccessToken;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\AccessToken\Command\CreateAccessTokenCommand;
use OAuth2Framework\Component\Core\AccessToken\Command\CreateAccessTokenCommandHandler;
use OAuth2Framework\Component\Core\AccessToken\Command\RevokeAccessTokenCommand;
use OAuth2Framework\Component\Core\AccessToken\Command\RevokeAccessTokenCommandHandler;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group AccessTokenCommand
 */
class AccessTokenCommandTest extends TestCase
{
    /**
     * @test
     */
    public function anAccessTokenCanBeCreatedUsingTheCommand()
    {
        $accessTokenId = AccessTokenId::create('ACCESS_TOKEN_ID');
        $command = CreateAccessTokenCommand::create(
            $accessTokenId,
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1year'),
            DataBag::create([]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $repository = $this->prophesize(AccessTokenRepository::class);
        $repository->find($accessTokenId)->willReturn(null)->shouldBeCalled();
        $repository->save(Argument::type(AccessToken::class))->shouldBeCalled();

        $handler = new CreateAccessTokenCommandHandler(
            $repository->reveal()
        );
        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anAccessTokenCanBeRevokedUsingTheCommand()
    {
        $accessTokenId = AccessTokenId::create('ACCESS_TOKEN_ID');
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            UserAccountId::create('USER_ACCOUNT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            DataBag::create([]),
            new \DateTimeImmutable('now +1year'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $command = RevokeAccessTokenCommand::create(
            $accessTokenId
        );

        $repository = $this->prophesize(AccessTokenRepository::class);
        $repository->find($accessTokenId)->willReturn($accessToken)->shouldBeCalled();
        $repository->save(Argument::type(AccessToken::class))->shouldBeCalled();

        $handler = new RevokeAccessTokenCommandHandler(
            $repository->reveal()
        );
        $handler->handle($command);
    }
}
