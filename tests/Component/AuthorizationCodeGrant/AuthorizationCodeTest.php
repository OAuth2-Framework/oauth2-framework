<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AuthorizationCodeTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAuthorizationCodeId(): void
    {
        $authorizationCodeId = new AuthorizationCodeId('AUTHORIZATION_CODE_ID');

        static::assertInstanceOf(AuthorizationCodeId::class, $authorizationCodeId);
        static::assertSame('AUTHORIZATION_CODE_ID', $authorizationCodeId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAuthorizationCode(): void
    {
        $authorizationCode = new AuthorizationCode(
            new AuthorizationCodeId('AUTHORIZATION_CODE_ID'),
            new ClientId('CLIENT_ID'),
            new UserAccountId('USER_ACCOUNT_ID'),
            [],
            'http://localhost',
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            new DataBag([]),
            new DataBag([]),
            new ResourceServerId('RESOURCE_SERVER_ID')
        );
        $authorizationCode->markAsUsed();

        static::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        static::assertSame('AUTHORIZATION_CODE_ID', $authorizationCode->getId()->getValue());
    }
}
