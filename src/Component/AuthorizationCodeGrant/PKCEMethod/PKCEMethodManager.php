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

    public static function create(): self
    {
        return new self();
    }

    public function add(PKCEMethod $method): self
    {
        $this->pkceMethods[$method->name()] = $method;

        return $this;
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
