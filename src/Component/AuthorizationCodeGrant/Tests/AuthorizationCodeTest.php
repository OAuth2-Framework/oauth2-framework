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

use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainConverter;
use OAuth2Framework\Component\Core\Domain\DomainUriLoader;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCode;
use OAuth2Framework\Component\AuthorizationCodeGrant\AuthorizationCodeId;
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
        $authorizationCodeId = AuthorizationCodeId::create('AUTHORIZATION_CODE_ID');

        self::assertInstanceOf(AuthorizationCodeId::class, $authorizationCodeId);
        self::assertEquals('AUTHORIZATION_CODE_ID', $authorizationCodeId->getValue());
        self::assertEquals('"AUTHORIZATION_CODE_ID"', json_encode($authorizationCodeId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnAuthorizationCode()
    {
        $authorizationCode = AuthorizationCode::createEmpty();
        $authorizationCode = $authorizationCode->create(
            AuthorizationCodeId::create('AUTHORIZATION_CODE_ID'),
            ClientId::create('CLIENT_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            [],
            'http://localhost',
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            DataBag::create([]),
            DataBag::create([]),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $authorizationCode = $authorizationCode->markAsUsed();
        $authorizationCode = $authorizationCode->markAsRevoked();
        $events = $authorizationCode->recordedMessages();

        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/authorization-code/1.0/schema","type":"OAuth2Framework\\\\Component\\\\AuthorizationCodeGrant\\\\AuthorizationCode","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{},"metadatas":{},"is_revoked":true,"resource_owner_id":"USER_ACCOUNT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\UserAccount\\\\UserAccountId","resource_server_id":"RESOURCE_SERVER_ID","auth_code_id":"AUTHORIZATION_CODE_ID","query_parameters":{},"redirect_uri":"http://localhost","is_used":true}', $this->getDomainConverter()->toJson($authorizationCode));
        self::assertEquals('AUTHORIZATION_CODE_ID', $authorizationCode->getTokenId()->getValue());
        self::assertCount(3, $events);
    }

    /**
     * @test
     */
    public function iCanCreateAnAuthorizationCodeFromDomainObject()
    {
        $json = '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/authorization-code/1.0/schema","type":"OAuth2Framework\\\\Component\\\\AuthorizationCodeGrant\\\\AuthorizationCode","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{},"metadatas":{},"is_revoked":true,"resource_owner_id":"USER_ACCOUNT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Core\\\\UserAccount\\\\UserAccountId","resource_server_id":"RESOURCE_SERVER_ID","auth_code_id":"AUTHORIZATION_CODE_ID","query_parameters":{},"redirect_uri":"http://localhost","is_used":true}';
        $authorizationCode = $this->getDomainConverter()->fromJson($json);
        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
    }

    /**
     * @test
     */
    public function iCanAnAuthorizationCodeUsingEvents()
    {
        $events = [
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/created/1.0/schema","type":"OAuth2Framework\\\\Component\\\\AuthorizationCodeGrant\\\\Event\\\\AuthorizationCodeCreatedEvent","domain_id":"AUTHORIZATION_CODE_ID","payload":{"user_account_id":"USER_ACCOUNT_ID","client_id":"CLIENT_ID","expires_at":1264683600,"parameters":{},"metadatas":{},"redirect_uri":"http://localhost","query_parameters":{},"resource_server_id":"RESOURCE_SERVER_ID"}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/marked-as-used/1.0/schema","type":"OAuth2Framework\\\\Component\\\\AuthorizationCodeGrant\\\\Event\\\\AuthorizationCodeMarkedAsUsedEvent","domain_id":"AUTHORIZATION_CODE_ID"}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/authorization-code/revoked/1.0/schema","type":"OAuth2Framework\\\\Component\\\\AuthorizationCodeGrant\\\\Event\\\\AuthorizationCodeRevokedEvent","domain_id":"AUTHORIZATION_CODE_ID"}',
        ];
        $authorizationCode = AuthorizationCode::createEmpty();

        foreach ($events as $event) {
            $eventObject = $this->getDomainConverter()->fromJson($event);
            $authorizationCode = $authorizationCode->apply($eventObject);
        }
        self::assertInstanceOf(AuthorizationCode::class, $authorizationCode);
    }

    /**
     * @var DomainConverter|null
     */
    private $domainConverter;

    /**
     * @return DomainConverter
     */
    private function getDomainConverter(): DomainConverter
    {
        if (null === $this->domainConverter) {
            $domainUriLoader = new DomainUriLoader();
            //Domain Objects
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/model/token/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Core/Token', '/Token-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/model/authorization-code/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/AuthorizationCode-1.0.json'));

            // Events
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/event/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Core/Event', '/Event-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/authorization-code/created/1.0/schema', sprintf('file://%s%s', __DIR__.'/../Event', '/AuthorizationCodeCreatedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/authorization-code/marked-as-used/1.0/schema', sprintf('file://%s%s', __DIR__.'/../Event', '/AuthorizationCodeMarkedAsUsedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/authorization-code/revoked/1.0/schema', sprintf('file://%s%s', __DIR__.'/../Event', '/AuthorizationCodeRevokedEvent-1.0.json'));
            $this->domainConverter = new DomainConverter($domainUriLoader);
        }

        return $this->domainConverter;
    }
}
