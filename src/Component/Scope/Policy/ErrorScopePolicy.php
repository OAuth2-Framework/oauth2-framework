<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;
use RuntimeException;

final class ErrorScopePolicy implements ScopePolicy
{
    public function name(): string
    {
        return 'error';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        throw new RuntimeException('No scope was requested.');
    }
}
