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

namespace OAuth2Framework\Component\Server\AuthorizationCodeGrant\Tests;

use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\AuthorizationCodeRepository;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\CreateAuthorizationCodeCommand;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\CreateAuthorizationCodeCommandHandler;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\MarkAuthorizationCodeAsUsedCommand;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\MarkAuthorizationCodeAsUsedCommandHandler;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\RevokeAuthorizationCodeCommand;
use OAuth2Framework\Component\Server\AuthorizationCodeGrant\Command\RevokeAuthorizationCodeCommandHandler;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @group Command
 * @group AuthorizationCodeGrantType
 */
final class AuthorizationCodeCommandsTest extends TestCase
{
    /**
     * @test
     */
    public function anAuthorizationCodeCanBeCreatedUsingTheCommand()
    {
        $authorizationCodeId = AuthorizationCodeId::create('AUTHORIZATION_CODE_ID');
        $command = CreateAuthorizationCodeCommand::create(
            $authorizationCodeId,
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            [],
            'http://localhost:8000/',
            new \DateTimeImmutable('now +1year'),
            DataBag::create([]),
            DataBag::create([]),
            [],
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );

        $repository = $this->prophesize(AuthorizationCodeRepository::class);
        $repository->find($authorizationCodeId)->willReturn(null)->shouldBeCalled();
        $repository->save(Argument::type(AuthorizationCode::class))->shouldBeCalled();

        $handler = new CreateAuthorizationCodeCommandHandler(
            $repository->reveal()
        );

        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anAuthorizationCodeCanBeMarkedAsUsedUsingTheCommand()
    {
        $authorizationCodeId = AuthorizationCodeId::create('AUTHORIZATION_CODE_ID');
        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            $authorizationCodeId,
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            [],
            'http://localhost:8000/',
            new \DateTimeImmutable('now +1year'),
            DataBag::create([]),
            DataBag::create([]),
            [],
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $command = MarkAuthorizationCodeAsUsedCommand::create(AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'));

        $repository = $this->prophesize(AuthorizationCodeRepository::class);
        $repository->find($authorizationCodeId)->willReturn($authorizationCode)->shouldBeCalled();
        $repository->save(Argument::type(AuthorizationCode::class))->shouldBeCalled();

        $handler = new MarkAuthorizationCodeAsUsedCommandHandler(
            $repository->reveal()
        );

        $handler->handle($command);
    }

    /**
     * @test
     */
    public function anAuthorizationCodeCanBeMarkedAsDeletedUsingTheCommand()
    {
        $authorizationCodeId = AuthorizationCodeId::create('AUTHORIZATION_CODE_ID');
        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            $authorizationCodeId,
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            [],
            'http://localhost:8000/',
            new \DateTimeImmutable('now +1year'),
            DataBag::create([]),
            DataBag::create([]),
            [],
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $command = RevokeAuthorizationCodeCommand::create(AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'));

        $repository = $this->prophesize(AuthorizationCodeRepository::class);
        $repository->find($authorizationCodeId)->willReturn($authorizationCode)->shouldBeCalled();
        $repository->save(Argument::type(AuthorizationCode::class))->shouldBeCalled();

        $handler = new RevokeAuthorizationCodeCommandHandler(
            $repository->reveal()
        );

        $handler->handle($command);
    }
}