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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

class ClientRepository implements \OAuth2Framework\Component\Core\Client\ClientRepository
{
    /**
     * @var Client[]
     */
    private $clients = [];

    /**
     * ClientRepository constructor.
     */
    public function __construct()
    {
        $this->populateClients();
    }

    /**
     * {@inheritdoc}
     */
    public function find(ClientId $clientId): ? Client
    {
        return array_key_exists($clientId->getValue(), $this->clients) ? $this->clients[$clientId->getValue()] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Client $client)
    {
        $this->clients[$client->getPublicId()->getValue()] = $client;
    }

    private function populateClients()
    {
        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID_1'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
                'grant_types' => [],
            ]),
            UserAccountId::create('USER_ACCOUNT_1')
        );
        $client->eraseMessages();
        $this->save($client);

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID_2'),
            DataBag::create([
                'token_endpoint_auth_method' => 'none',
                'grant_types' => ['client_credentials', 'refresh_token', 'authorization_code', 'password', 'implicit'],
            ]),
            UserAccountId::create('USER_ACCOUNT_1')
        );
        $client->eraseMessages();
        $this->save($client);

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID_3'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_post',
                'grant_types' => ['client_credentials', 'refresh_token', 'authorization_code', 'password', 'implicit'],
                'client_secret' => 'secret',
            ]),
            UserAccountId::create('USER_ACCOUNT_1')
        );
        $client->eraseMessages();
        $this->save($client);

        $client = Client::createEmpty();
        $client = $client->create(
            ClientId::create('CLIENT_ID_4'),
            DataBag::create([
                'token_endpoint_auth_method' => 'client_secret_jwt',
                'grant_types' => ['urn:ietf:params:oauth:grant-type:jwt-bearer'],
                'client_secret' => 'secret',
            ]),
            UserAccountId::create('USER_ACCOUNT_1')
        );
        $client->eraseMessages();
        $this->save($client);
    }
}
