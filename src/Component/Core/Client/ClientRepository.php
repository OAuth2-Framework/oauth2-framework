<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface ClientRepository
{
    /**
     * Save the client.
     */
    public function save(Client $client): void;

    /**
     * Get a client using its Id.
     *
     * @return Client|null return the client object or null if no client is found
     */
    public function find(ClientId $clientId): ?Client;

    /**
     * Creates an unique client ID.
     */
    public function createClientId(): ClientId;

    /**
     * Creates an empty client.
     */
    public function create(ClientId $clientId, DataBag $parameters, ?UserAccountId $ownerId): Client;
}
