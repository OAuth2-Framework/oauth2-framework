<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope;

interface ScopeRepository
{
    public function has(string $scope): bool;

    public function get(string $scope): Scope;

    /**
     * @return Scope[]
     */
    public function all(): array;
}
