<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

final class NoScopePolicy implements ScopePolicy
{
    public static function create(): static
    {
        return new self();
    }

    public function name(): string
    {
        return 'none';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        return $scope;
    }
}
