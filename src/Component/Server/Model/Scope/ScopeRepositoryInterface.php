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
     * @param string $scopePolicy
     *
     * @return bool
     */
    public function hasScopePolicy(string $scopePolicy): bool;

    /**
     * @return ScopePolicyInterface
     */
    public function getDefaultScopePolicy(): ScopePolicyInterface;

    /**
     * This function returns the scope policy. If a valid Client object is set as parameter, the function will return scope policy for the client.
     *
     * @param string $scopePolicyName Scope policy
     *
     * @throws \InvalidArgumentException
     *
     * @return ScopePolicyInterface
     */
    public function getScopePolicy(string $scopePolicyName): ScopePolicyInterface;

    /**
     * This function returns the scope policy. If a valid Client object is set as parameter, the function will return scope policy for the client.
     *
     * @param Client $client A client
     *
     * @return ScopePolicyInterface
     */
    public function getScopePolicyForClient(Client $client): ScopePolicyInterface;

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
     * This function check if the scopes respect the scope policy for the client.
     *
     * @param string[] $scope  The scopes
     * @param Client   $client A client
     *
     * @return string[] An array scopes according to the scope policy
     */
    public function checkScopePolicy(array $scope, Client $client): array;

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
