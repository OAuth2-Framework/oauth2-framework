<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\DataBag;

use function array_key_exists;
use ArrayIterator;
use Assert\Assertion;
use function count;
use Countable;
use IteratorAggregate;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;
use JsonSerializable;

class DataBag implements IteratorAggregate, Countable, JsonSerializable
{
    public function __construct(
        private array $parameters
    ) {
    }

    public static function create(array $parameters = []): self
    {
        return new self($parameters);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param mixed|null $default
     *
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->parameters[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    public static function createFromString(string $data): self
    {
        $json = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        Assertion::eq(JSON_ERROR_NONE, json_last_error(), 'Invalid data');
        Assertion::isArray($json, 'Invalid data');

        return new self($json);
    }

    public function jsonSerialize(): array
    {
        return $this->parameters;
    }
}
