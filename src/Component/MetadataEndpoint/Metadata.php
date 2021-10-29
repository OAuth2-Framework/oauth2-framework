<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\MetadataEndpoint;

use function array_key_exists;
use InvalidArgumentException;

class Metadata
{
    private array $values = [];

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * @return mixed|null
     */
    public function get(string $key)
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException(sprintf('The value with key "%s" does not exist.', $key));
        }

        return $this->values[$key];
    }

    /**
     * @param mixed|null $value
     */
    public function set(string $key, $value): void
    {
        $this->values[$key] = $value;
    }

    public function all(): array
    {
        return $this->values;
    }
}
