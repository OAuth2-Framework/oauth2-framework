<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\ClientRepository;

final class ClientFixtures implements FixtureInterface
{
    private $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $datum) {
            $client = $this->clientRepository->create(
                new ClientId($datum['client_id']),
                new DataBag($datum['parameter']),
                new UserAccountId($datum['owner_id'])
            );
            $this->clientRepository->save($client);
        }
    }

    private function getData(): array
    {
        return [
            [
                'client_id' => 'CLIENT_ID_1',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'none',
                    'grant_types' => [],
                ],
            ],
            [
                'client_id' => 'CLIENT_ID_2',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'none',
                    'grant_types' => ['client_credentials', 'refresh_token', 'authorization_code', 'password', 'implicit'],
                    'response_types' => ['code'],
                    'redinect_uris' => [
                        'https://exxample.com/cb/?foo=bar',
                    ],
                ],
            ],
            [
                'client_id' => 'CLIENT_ID_3',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'client_secret_post',
                    'grant_types' => ['client_credentials', 'refresh_token', 'authorization_code', 'password', 'implicit'],
                    'client_secret' => 'secret',
                ],
            ],
            [
                'client_id' => 'CLIENT_ID_4',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'client_secret_jwt',
                    'grant_types' => ['urn:ietf:params:oauth:grant-type:jwt-bearer'],
                    'client_secret' => 'secret',
                ],
            ],
            [
                'client_id' => 'CLIENT_ID_5',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'client_secret_basic',
                    'grant_types' => ['client_credentials', 'refresh_token', 'authorization_code', 'password', 'implicit'],
                    'client_secret' => 'secret',
                ],
            ],
        ];
    }
}
