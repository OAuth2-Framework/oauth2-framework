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
use OAuth2Framework\Component\Server\Util\Scope;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var string[]
     */
    private $availableScopes = [];

    /**
     * ScopeManager constructor.
     *
     * @param array $availableScopes
     */
    public function __construct(array $availableScopes = [])
    {
        $this->availableScopes = $availableScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedScopes(): array
    {
        return $this->availableScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableScopesForClient(Client $client): array
    {
        return ($client->has('scope')) ? $this->convertToArray($client->get('scope')) : $this->getSupportedScopes();
    }

    /**
     * {@inheritdoc}
     */
    public function areRequestedScopesAvailable(array $requestedScopes, array $availableScopes): bool
    {
        return 0 === count(array_diff($requestedScopes, $availableScopes));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToArray(string $scopes): array
    {
        Scope::checkScopeCharset($scopes);
        $scopes = explode(' ', $scopes);

        foreach ($scopes as $scope) {
            Scope::checkScopeUsedOnce($scope, $scopes);
        }

        return $scopes;
    }
}
