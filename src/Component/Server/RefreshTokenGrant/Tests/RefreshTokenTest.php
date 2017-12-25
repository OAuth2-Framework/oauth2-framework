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

namespace OAuth2Framework\Component\Server\RefreshTokenGrant\Tests;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\DomainConverter;
use OAuth2Framework\Component\Server\Core\DomainUriLoader;
use OAuth2Framework\Component\Server\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshToken;
use OAuth2Framework\Component\Server\RefreshTokenGrant\RefreshTokenId;
use PHPUnit\Framework\TestCase;

/**
 * @group RefreshToken
 */
final class RefreshTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnRefreshTokenId()
    {
        $refreshTokenId = RefreshTokenId::create('REFRESH_TOKEN_ID');

        self::assertInstanceOf(RefreshTokenId::class, $refreshTokenId);
        self::assertEquals('REFRESH_TOKEN_ID', $refreshTokenId->getValue());
        self::assertEquals('"REFRESH_TOKEN_ID"', json_encode($refreshTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnRefreshToken()
    {
        $refreshToken = RefreshToken::createEmpty();
        $refreshToken = $refreshToken->create(
            RefreshTokenId::create('REFRESH_TOKEN_ID'),
            ClientId::create('CLIENT_ID'),
            ClientId::create('CLIENT_ID'),
            DataBag::create([
                'refresh_token_id' => 'REFRESH_TOKEN_ID',
            ]),
            DataBag::create([]),
            [],
            new \DateTimeImmutable('2010-01-28T15:00:00+02:00'),
            ResourceServerId::create('RESOURCE_SERVER_ID')
        );
        $refreshToken = $refreshToken->addAccessToken(AccessTokenId::create('ACCESS_TOKEN_ID'));
        $refreshToken = $refreshToken->markAsRevoked();
        $events = $refreshToken->recordedMessages();

        self::assertInstanceOf(RefreshToken::class, $refreshToken);
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Server\\\\RefreshTokenGrant\\\\RefreshToken","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"metadatas":{},"scopes":[],"is_revoked":true,"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Server\\\\Core\\\\Client\\\\ClientId","resource_server_id":"RESOURCE_SERVER_ID","refresh_token_id":"REFRESH_TOKEN_ID","access_token_ids":["ACCESS_TOKEN_ID"]}', $this->getDomainConverter()->toJson($refreshToken));
        self::assertEquals('REFRESH_TOKEN_ID', $refreshToken->getTokenId()->getValue());
        self::assertCount(3, $events);
    }

    /**
     * @test
     */
    public function iCanCreateAnRefreshTokenFromDomainObject()
    {
        $json = '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Server\\\\RefreshTokenGrant\\\\RefreshToken","expires_at":1264683600,"client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"metadatas":{},"scopes":[],"is_revoked":true,"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Server\\\\Core\\\\Client\\\\ClientId","resource_server_id":"RESOURCE_SERVER_ID","refresh_token_id":"REFRESH_TOKEN_ID","access_token_ids":["ACCESS_TOKEN_ID"]}';
        $refreshToken = $this->getDomainConverter()->fromJson($json);
        self::assertInstanceOf(RefreshToken::class, $refreshToken);
    }

    /**
     * @test
     */
    public function iCanAnRefreshTokenUsingEvents()
    {
        $events = [
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema","event_id":"f29c3cf9-7630-4d0c-861e-9005d0e33c61","type":"OAuth2Framework\\\\Component\\\\Server\\\\RefreshTokenGrant\\\\Event\\\\RefreshTokenCreatedEvent","domain_id":"REFRESH_TOKEN_ID","recorded_on":1512893793,"payload":{"resource_owner_id":"CLIENT_ID","resource_owner_class":"OAuth2Framework\\\\Component\\\\Server\\\\Core\\\\Client\\\\ClientId","client_id":"CLIENT_ID","parameters":{"refresh_token_id":"REFRESH_TOKEN_ID"},"expires_at":1264683600,"scopes":[],"metadatas":{},"resource_server_id":"RESOURCE_SERVER_ID"}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema","event_id":"b0316425-4d7f-4b4d-8882-78a80daf4bde","type":"OAuth2Framework\\\\Component\\\\Server\\\\RefreshTokenGrant\\\\Event\\\\AccessTokenAddedToRefreshTokenEvent","domain_id":"REFRESH_TOKEN_ID","recorded_on":1512893793,"payload":{"access_token_id":"ACCESS_TOKEN_ID"}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/refresh-token/revoked/1.0/schema","event_id":"625a79ec-cfd7-4a9c-9b19-b90f7a0ccafa","type":"OAuth2Framework\\\\Component\\\\Server\\\\RefreshTokenGrant\\\\Event\\\\RefreshTokenRevokedEvent","domain_id":"REFRESH_TOKEN_ID","recorded_on":1512893793}',
        ];
        $refreshToken = RefreshToken::createEmpty();

        foreach ($events as $event) {
            $eventObject = $this->getDomainConverter()->fromJson($event);
            $refreshToken = $refreshToken->apply($eventObject);
        }
        self::assertInstanceOf(RefreshToken::class, $refreshToken);
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
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/model/refresh-token/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/RefreshToken-1.0.json'));

            // Events
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/event/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Core/Event', '/Event-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/refresh-token/created/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/Event/RefreshTokenCreatedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/refresh-token/access-token-added/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/Event/AccessTokenAddedToRefreshTokenEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/refresh-token/revoked/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/Event/RefreshTokenRevokedEvent-1.0.json'));
            $this->domainConverter = new DomainConverter($domainUriLoader);
        }

        return $this->domainConverter;
    }
}
