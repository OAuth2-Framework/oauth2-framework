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

namespace OAuth2Framework\Component\Server\Core\Tests\AccessToken;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenHandler;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group AccessTokenHandlerManager
 */
final class AccessTokenHandlerManagerTest extends TestCase
{
    /**
     * @test
     */
    public function theAccessTokenHandlerManager()
    {
        $accessTokenId = AccessTokenId::create('ACCESS_TOKEN_ID');
        $accessToken = AccessToken::createEmpty();
        $accessToken = $accessToken->create(
            $accessTokenId,
            UserAccountId::create('USER_ACCOUNT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([]),
            DataBag::create([]),
            [],
            new \DateTimeImmutable('now +1year'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $handler = $this->prophesize(AccessTokenHandler::class);
        $handler->find($accessTokenId)->willReturn($accessToken)->shouldBeCalled();
        $handlerManager = new AccessTokenHandlerManager();
        $handlerManager->add($handler->reveal());

        $accessToken = $handlerManager->find($accessTokenId);
        self::assertInstanceOf(AccessToken::class, $accessToken);
    }
}
