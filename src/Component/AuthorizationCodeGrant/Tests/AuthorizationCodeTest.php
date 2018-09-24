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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\Tests;

use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group AuthorizationCode
 */
final class AuthorizationCodeTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAuthorizationCodeId()
    {
        $authorizationCodeId = new AuthorizationCodeId('AUTHORIZATION_CODE_ID');

        static::assertInstanceOf(AuthorizationCodeId::class, $authorizationCodeId);
        static::assertEquals('AUTHORIZATION_CODE_ID', $authorizationCodeId->getValue());
        static::assertEquals('"AUTHORIZATION_CODE_ID"', \json_encode($authorizationCodeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAuthorizationCode()
    {
        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('AUTHORIZATION_CODE_ID'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            [],
            'http://localhost',
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $authorizationCode->markAsUsed();
        $authorizationCode->markAsRevoked();

        static::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        static::assertEquals('{"expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{},"metadatas":{},"is_revoked":true,"resource_owner_id":"USER_ACCOUNT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\UserAccount\\\\UserAccountId","resource_server_id":"RESOURCE_SERVER_ID","auth_code_id":"AUTHORIZATION_CODE_ID","query_parameters":{},"redirect_uri":"http://localhost","is_used":true}', \json_encode($authorizationCode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        static::assertEquals('AUTHORIZATION_CODE_ID', $authorizationCode->getTokenId()->getValue());
    }
}
