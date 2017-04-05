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

namespace OAuth2Framework\Component\Server\Model\Scope;

use OAuth2Framework\Component\Server\Model\Client\Client;

interface ScopeRepositoryInterface
{
    /**
     * @return string[]
     */
    public function getSupportedScopes(): array;

    /**
     * This function returns the available scopes. If a valid Client object is set as parameter, the function will return available scopes for the client.
     *
     * @param Client $client A client
     *
     * @return string[] Return an array scope
     */
    public function getAvailableScopesForClient(Client $client): array;

    /**
     * @param string[] $requestedScopes An array of scopes that represents requested scopes
     * @param string[] $availableScopes An array of scopes that represents available scopes
     *
     * @return bool Return true if the requested scope is within the available scope
     */
    public function areRequestedScopesAvailable(array $requestedScopes, array $availableScopes): bool;

    /**
     * Convert a string that contains at least one scope to an array of scopes.
     *
     * @param string $scopes The string to convert
     *
     * @return string[] An array of scopes
     */
    public function convertToArray(string $scopes): array;
}
