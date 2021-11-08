<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\Component\AuthorizationCodeGrant;

use DateTimeImmutable;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\Component\OAuth2TestCase;
use OAuth2Framework\Tests\TestBundle\Entity\AuthorizationCode;

/**
 * @internal
 */
final class AuthorizationCodeTest extends OAuth2TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnAuthorizationCodeId(): void
    {
        $authorizationCodeId = AuthorizationCodeId::create('AUTHORIZATION_CODE_ID');

        static::assertSame('AUTHORIZATION_CODE_ID', $authorizationCodeId->getValue());
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAuthorizationCode(): void
    {
        $authorizationCode = new AuthorizationCode(
            AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'),
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            [],
            'http://localhost',
            new DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            DataBag::create([]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $authorizationCode->markAsUsed();

        static::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        static::assertSame('AUTHORIZATION_CODE_ID', $authorizationCode->getId()->getValue());
    }
}
