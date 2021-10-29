<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

use function array_key_exists;

class PKCEMethodManager
{
    /**
     * @var PKCEMethod[]
     */
    private array $pkceMethods = [];

    public function add(PKCEMethod $method): void
    {
        $this->pkceMethods[$method->name()] = $method;
    }

    public function has(string $method): bool
    {
        return array_key_exists($method, $this->pkceMethods);
    }

    public function get(string $method): PKCEMethod
    {
        return $this->pkceMethods[$method];
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->pkceMethods);
    }
}
