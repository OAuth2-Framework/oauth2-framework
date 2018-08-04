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
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandler;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
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
        $accessTokenId = new AccessTokenId('ACCESS_TOKEN_ID');
        $accessToken = new AccessToken(
            $accessTokenId,
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            new \DateTimeImmutable('now +1year'),
            new DataBag([]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $handler = $this->prophesize(AccessTokenHandler::class);
        $handler->find($accessTokenId)->willReturn($accessToken)->shouldBeCalled();
        $handlerManager = new AccessTokenHandlerManager();
        $handlerManager->add($handler->reveal());

        $accessToken = $handlerManager->find($accessTokenId);
        static::assertInstanceOf(AccessToken::class, $accessToken);
    }
}
