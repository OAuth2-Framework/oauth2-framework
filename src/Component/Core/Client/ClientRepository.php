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

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;

interface ClientRepository
{
    /**
     * Save the client.
     */
    public function save(Client $client);

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
