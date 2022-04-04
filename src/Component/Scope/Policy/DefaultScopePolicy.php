<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

final class DefaultScopePolicy implements ScopePolicy
{
    public function __construct(
        private readonly string $defaultScopes
    ) {
    }

    public static function create(string $defaultScopes): static
    {
        return new self($defaultScopes);
    }

    public function name(): string
    {
        return 'default';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        return $client->has('default_scope') ? $client->get('default_scope') : $this->getDefaultScopes();
    }

    private function getDefaultScopes(): string
    {
        return $this->defaultScopes;
    }
}
