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

interface ClientRepository
{
    /**
     * Save the client.
     *
     * @param Client $client
     */
    public function save(Client $client);

    /**
     * Get a client using its Id.
     *
     * @param ClientId $clientId
     *
     * @return null|Client return the client object or null if no client is found
     */
    public function find(ClientId $clientId): ? Client;
}
