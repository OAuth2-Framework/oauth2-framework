<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenEndpoint;

use function array_key_exists;
use InvalidArgumentException;

class GrantTypeManager
{
    /**
     * @var GrantType[]
     */
    private array $grantTypes = [];

    public function add(GrantType $grantType): void
    {
        $this->grantTypes[$grantType->name()] = $grantType;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->grantTypes);
    }

    public function get(string $name): GrantType
    {
        if (! $this->has($name)) {
            throw new InvalidArgumentException(sprintf('The grant type "%s" is not supported.', $name));
        }

        return $this->grantTypes[$name];
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->grantTypes);
    }
}
