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

namespace OAuth2Framework\Component\Server\Core\Scope;

use OAuth2Framework\Component\Server\Core\Client\Client;

interface ScopeRepository
{
    /**
     * @param string $scope
     *
     * @return bool
     */
    public function has(string $scope): bool;

    /**
     * @return string[]
     */
    public function getSupportedScopes(): array;

    /**
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
}
