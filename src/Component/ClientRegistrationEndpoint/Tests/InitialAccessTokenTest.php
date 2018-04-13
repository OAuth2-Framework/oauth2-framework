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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint\Tests;

use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessToken;
use OAuth2Framework\Component\ClientRegistrationEndpoint\InitialAccessTokenId;
use OAuth2Framework\Component\Core\Domain\DomainConverter;
use OAuth2Framework\Component\Core\Domain\DomainUriLoader;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group InitialAccessToken
 */
class InitialAccessTokenTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAnInitialAccessTokenId()
    {
        $initialAccessTokenId = InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID');

        self::assertInstanceOf(InitialAccessTokenId::class, $initialAccessTokenId);
        self::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessTokenId->getValue());
        self::assertEquals('"INITIAL_ACCESS_TOKEN_ID"', json_encode($initialAccessTokenId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAndRevokedAnInitialAccessToken()
    {
        $initialAccessToken = InitialAccessToken::createEmpty();
        $initialAccessToken = $initialAccessToken->create(
            InitialAccessTokenId::create('INITIAL_ACCESS_TOKEN_ID'),
            UserAccountId::create('USER_ACCOUNT_ID'),
            null
        );
        $initialAccessToken = $initialAccessToken->markAsRevoked();
        $events = $initialAccessToken->recordedMessages();

        self::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\ClientRegistrationEndpoint\\\\InitialAccessToken","initial_access_token_id":"INITIAL_ACCESS_TOKEN_ID","user_account_id":"USER_ACCOUNT_ID","expires_at":null,"is_revoked":true}', $this->getDomainConverter()->toJson($initialAccessToken));
        self::assertEquals('INITIAL_ACCESS_TOKEN_ID', $initialAccessToken->getTokenId()->getValue());
        self::assertCount(2, $events);
    }

    /**
     * @test
     */
    public function iCanCreateAnInitialAccessTokenFromDomainObject()
    {
        $json = '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema","type":"OAuth2Framework\\\\Component\\\\ClientRegistrationEndpoint\\\\InitialAccessToken","initial_access_token_id":"INITIAL_ACCESS_TOKEN_ID","user_account_id":"USER_ACCOUNT_ID","expires_at":null,"is_revoked":true}';
        $initialAccessToken = $this->getDomainConverter()->fromJson($json);
        self::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
    }

    /**
     * @test
     */
    public function iCanAnInitialAccessTokenUsingEvents()
    {
        $events = [
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema","type":"OAuth2Framework\\\\Component\\\\ClientRegistrationEndpoint\\\\Event\\\\InitialAccessTokenCreatedEvent","domain_id":"INITIAL_ACCESS_TOKEN_ID","payload":{"user_account_id":"USER_ACCOUNT_ID","expires_at":null}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema","type":"OAuth2Framework\\\\Component\\\\ClientRegistrationEndpoint\\\\Event\\\\InitialAccessTokenRevokedEvent","domain_id":"INITIAL_ACCESS_TOKEN_ID"}',
        ];
        $initialAccessToken = InitialAccessToken::createEmpty();

        foreach ($events as $event) {
            $eventObject = $this->getDomainConverter()->fromJson($event);
            $initialAccessToken = $initialAccessToken->apply($eventObject);
        }
        self::assertInstanceOf(InitialAccessToken::class, $initialAccessToken);
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
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/model/initial-access-token/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/InitialAccessToken-1.0.json'));

            // Events
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/event/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Core/Event', '/Event-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/created/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/Event/InitialAccessTokenCreatedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/initial-access-token/revoked/1.0/schema', sprintf('file://%s%s', __DIR__.'/..', '/Event/InitialAccessTokenRevokedEvent-1.0.json'));
            $this->domainConverter = new DomainConverter($domainUriLoader);
        }

        return $this->domainConverter;
    }
}
