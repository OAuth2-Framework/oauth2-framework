<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\Core\Client\Client as ClientInterface;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository as ClientRepositoryInterface;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use OAuth2Framework\Tests\TestBundle\Entity\Client;

final class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var array<string, ClientInterface>
     */
    private array $clients = [];

    public function __construct()
    {
        $this->load();
    }

    public function find(ClientId $clientId): ?ClientInterface
    {
        return $this->clients[$clientId->getValue()] ?? null;
    }

    public function save(ClientInterface $client): void
    {
        $this->clients[$client->getClientId()->getValue()] = $client;
    }

    public function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): ClientInterface
    {
        return new Client($clientId, $parameters, $ownerId);
    }

    public function createClientId(): ClientId
    {
        return new ClientId(bin2hex(random_bytes(32)));
    }

    private function load(): void
    {
        foreach ($this->getData() as $datum) {
            $client = $this->create(
                new ClientId($datum['client_id']),
                new DataBag($datum['parameter']),
                new UserAccountId($datum['owner_id'])
            );
            $this->save($client);
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
                    'grant_types' => [
                        'client_credentials',
                        'refresh_token',
                        'authorization_code',
                        'password',
                        'implicit',
                    ],
                    'response_types' => ['code'],
                    'redinect_uris' => ['https://exxample.com/cb/?foo=bar'],
                ],
            ],
            [
                'client_id' => 'CLIENT_ID_3',
                'owner_id' => 'USER_ACCOUNT_1',
                'parameter' => [
                    'token_endpoint_auth_method' => 'client_secret_post',
                    'grant_types' => [
                        'client_credentials',
                        'refresh_token',
                        'authorization_code',
                        'password',
                        'implicit',
                    ],
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
                    'grant_types' => [
                        'client_credentials',
                        'refresh_token',
                        'authorization_code',
                        'password',
                        'implicit',
                    ],
                    'client_secret' => 'secret',
                ],
            ],
        ];
    }
}
