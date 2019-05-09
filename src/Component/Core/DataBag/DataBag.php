<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\DataBag;

use ArrayIterator;

class DataBag implements \JsonSerializable, \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $parameters = [];

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->parameters);
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

    /**
     * @param mixed|null $value
     */
    public function set(string $key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }

    public function count(): int
    {
        return \count($this->parameters);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }
}
