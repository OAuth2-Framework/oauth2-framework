<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

interface ScopePolicy
{
    public function name(): string;

    /**
     * This function check if the scopes respect the scope policy for the client.
     *
     * @param string $scope  The scopes. This variable may be modified according to the scope policy
     * @param Client $client The client
     */
    public function applyScopePolicy(string $scope, Client $client): string;
}
