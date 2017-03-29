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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Client\ClientId;
use OAuth2Framework\Component\Server\Model\Client\ClientRepositoryInterface;
use OAuth2Framework\Component\Server\Model\DataBag\DataBag;
use OAuth2Framework\Component\Server\Model\Event\EventStoreInterface;
use OAuth2Framework\Component\Server\Model\UserAccount\UserAccountId;
use SimpleBus\Message\Recorder\RecordsMessages;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @var RecordsMessages
     */
    private $eventRecorder;

    /**
     * ClientRepository constructor.
     *
     * @param EventStoreInterface $eventStore
     * @param RecordsMessages     $eventRecorder
     */
    public function __construct(EventStoreInterface $eventStore, RecordsMessages $eventRecorder)
    {
        $this->eventStore = $eventStore;
        $this->eventRecorder = $eventRecorder;
        $this->createAndSaveClient(
            ClientId::create('client1'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'secret',
                'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code', 'urn:ietf:params:oauth:grant-type:jwt-bearer'],
                'response_types' => ['code', 'token', 'id_token', 'code token', 'code id_token', 'id_token token', 'code id_token token', 'none'],
                'redirect_uris' => ['https://example.com/'],
                'id_token_signed_response_alg' => 'ES256',
            ]),
            UserAccountId::create('User1')
        );

        $this->createAndSaveClient(
            ClientId::create('client2'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'none',
                'grant_types' => ['client_credentials', 'authorization_code'],
                'userinfo_signed_response_alg' => 'none',
            ]),
            UserAccountId::create('User1')
        );

        $this->createAndSaveClient(
            ClientId::create('client3'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'client_secret_jwt',
                'client_secret' => 'secret',
                'client_secret_expires_at' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
                'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code'],
            ]),
            UserAccountId::create('User1')
        );

        $this->createAndSaveClient(
            ClientId::create('client4'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'client_secret_post',
                'client_secret' => 'secret',
                'client_secret_expires_at' => (new \DateTimeImmutable('now + 1 day'))->getTimestamp(),
            ]),
            UserAccountId::create('User1')
        );

        $this->createAndSaveClient(
            ClientId::create('79b407fb-acc0-4880-ab98-254062c214ce'),
            DataBag::createFromArray([
                'registration_access_token' => 'JNWuIxHkTKtUmmtEpipDtPlTc3ordUNpSVVPLbQXKrFKyYVDR7N3k1ZzrHmPWXoibr2J2HrTSSozN6zIhHuypA',
                'grant_types' => [],
                'response_types' => [],
                'redirect_uris' => ['https://www.foo.com'],
                'software_statement' => 'eyJhbGciOiJFUzI1NiJ9.eyJzb2Z0d2FyZV92ZXJzaW9uIjoiMS4wIiwic29mdHdhcmVfbmFtZSI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNlbiI6Ik15IGFwcGxpY2F0aW9uIiwic29mdHdhcmVfbmFtZSNmciI6Ik1vbiBhcHBsaWNhdGlvbiJ9.88m8-YyguCCx1QNChwfNnMZ9APKpNC--nnfB1rVBpAYyHLixtsyMuuI09svqxuiRfTxwgXuRUvsg_5RozmtusQ',
                'software_version' => '1.0',
                'software_name' => 'My application',
                'software_name#en' => 'My application',
                'software_name#fr' => 'Mon application',
                'registration_client_uri' => 'https://www.config.example.com/client/79b407fb-acc0-4880-ab98-254062c214ce',
                'client_id_issued_at' => 1482177703,
            ]),
            UserAccountId::create('User1')
        );

        $this->createAndSaveClient(
            ClientId::create('DISABLED_CLIENT'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'secret',
                'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code', 'urn:ietf:params:oauth:grant-type:jwt-bearer'],
                'response_types' => ['code', 'token', 'id_token', 'code token', 'code id_token', 'id_token token', 'code id_token token', 'none'],
                'redirect_uris' => ['https://example.com/'],
                'id_token_signed_response_alg' => 'ES256',
            ]),
            UserAccountId::create('User1'),
            true
        );

        $this->createAndSaveClient(
            ClientId::create('client5'),
            DataBag::createFromArray([
                'token_endpoint_auth_method' => 'client_secret_basic',
                'client_secret' => 'secret',
                'client_secret_expires_at' => time() - 100,
                'grant_types' => ['client_credentials', 'password', 'refresh_token', 'authorization_code', 'urn:ietf:params:oauth:grant-type:jwt-bearer'],
                'response_types' => ['code', 'token', 'id_token', 'code token', 'code id_token', 'id_token token', 'code id_token token', 'none'],
                'redirect_uris' => ['https://example.com/'],
                'id_token_signed_response_alg' => 'ES256',
            ]),
            UserAccountId::create('User1')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(ClientId $clientId): ?Client
    {
        $client = null;
        $events = $this->eventStore->getEvents($clientId);
        if (!empty($events)) {
            $client = Client::createEmpty();
            foreach ($events as $event) {
                $client = $client->apply($event);
            }
        }

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client)
    {
        $events = $client->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
            $this->eventRecorder->record($event);
        }
        $client->eraseMessages();
    }

    private function createAndSaveClient(ClientId $clientId, DataBag $parameters, UserAccountId $ownerId = null, $markAsDeleted = false)
    {
        $client = Client::createEmpty();
        $client = $client->create(
            $clientId,
            $parameters,
            $ownerId
        );
        if (true === $markAsDeleted) {
            $client = $client->markAsDeleted();
        }
        $events = $client->recordedMessages();
        foreach ($events as $event) {
            $this->eventStore->save($event);
        }
        $client->eraseMessages();
        $this->save($client);
    }
}
