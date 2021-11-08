<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\Client;

final class ErrorScopePolicy implements ScopePolicy
{
    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'error';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        throw new InvalidArgumentException('No scope was requested.');
    }
}
