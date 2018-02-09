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

namespace OAuth2Framework\Component\Core\Tests\Client;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Domain\DomainConverter;
use OAuth2Framework\Component\Core\Domain\DomainUriLoader;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @group ClientCredentials
 */
class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function iCanCreateAClientId()
    {
        $clientId = ClientId::create('CLIENT_ID');

        self::assertInstanceOf(ClientId::class, $clientId);
        self::assertEquals('CLIENT_ID', $clientId->getValue());
        self::assertEquals('"CLIENT_ID"', json_encode($clientId, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @test
     */
    public function iCanCreateAClient()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID'),
            DataBag::create([
            ]),
            UserAccountId::create('USER_ACCOUNT_ID')
        );
        $client = $client->withParameters(DataBag::create([
            'token_endpoint_auth_method' => 'none',
        ]));
        $client = $client->withOwnerId(UserAccountId::create('NEW_USER_ACCOUNT_ID'));
        $client = $client->markAsDeleted();
        $events = $client->recordedMessages();

        self::assertInstanceOf(Client::class, $client);
        self::assertTrue($client->isPublic());
        self::assertTrue($client->isDeleted());
        self::assertEquals('{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Client","client_id":"CLIENT_ID","owner_id":"NEW_USER_ACCOUNT_ID","parameters":{"token_endpoint_auth_method":"none","client_id":"CLIENT_ID"},"is_deleted":true}', $this->getDomainConverter()->toJson($client));
        self::assertCount(4, $events);
    }

    /**
     * @test
     */
    public function iCanCreateAClientFromDomainObject()
    {
        $json = '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Client","client_id":"CLIENT_ID","owner_id":"NEW_USER_ACCOUNT_ID","parameters":{"token_endpoint_auth_method":"none","client_id":"CLIENT_ID"},"is_deleted":true}';
        $accessToken = $this->getDomainConverter()->fromJson($json);
        self::assertInstanceOf(Client::class, $accessToken);
    }

    /**
     * @test
     */
    public function iCanCreateAClientUsingEvents()
    {
        $events = [
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Event\\\\ClientCreatedEvent","domain_id":"CLIENT_ID","payload":{"user_account_id":"USER_ACCOUNT_ID","parameters":{}}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Event\\\\ClientParametersUpdatedEvent","domain_id":"CLIENT_ID","payload":{"parameters":{"token_endpoint_auth_method":"none"}}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Event\\\\ClientOwnerChangedEvent","domain_id":"CLIENT_ID","payload":{"new_owner_id":"NEW_USER_ACCOUNT_ID"}}',
            '{"$schema":"https://oauth2-framework.spomky-labs.com/schemas/events/client/deleted/1.0/schema","type":"OAuth2Framework\\\\Component\\\\Core\\\\Client\\\\Event\\\\ClientDeletedEvent","domain_id":"CLIENT_ID"}',
        ];
        $accessToken = Client::createEmpty();

        foreach ($events as $event) {
            $eventObject = $this->getDomainConverter()->fromJson($event);
            $accessToken = $accessToken->apply($eventObject);
        }
        self::assertInstanceOf(Client::class, $accessToken);
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
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/model/client/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Client', '/Client-1.0.json'));

            // Events
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/event/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Event', '/Event-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/client/created/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Client', '/Event/ClientCreatedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/client/deleted/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Client', '/Event/ClientDeletedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/client/owner-changed/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Client', '/Event/ClientOwnerChangedEvent-1.0.json'));
            $domainUriLoader->add('oauth2-framework.spomky-labs.com/schemas/events/client/parameters-updated/1.0/schema', sprintf('file://%s%s', __DIR__.'/../../Client', '/Event/ClientParametersUpdatedEvent-1.0.json'));
            $this->domainConverter = new DomainConverter($domainUriLoader);
        }

        return $this->domainConverter;
    }
}
