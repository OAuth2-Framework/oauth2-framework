<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\DataBag;

final class DataBag implements \JsonSerializable
{
    /**
     * @var array
     */
    private $parameters = [];

    private function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param array $parameters
     *
     * @return DataBag
     */
    public static function create(array $parameters): self
    {
        return new self($parameters);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->parameters[$key];
        }

        return $default;
    }

    /**
     * @param string     $key
     * @param null|mixed $value
     *
     * @return DataBag
     */
    public function with(string $key, $value): self
    {
        $clone = clone $this;
        $clone->parameters[$key] = $value;

        return $clone;
    }

    /**
     * @param string $key
     *
     * @return DataBag
     */
    public function without(string $key): self
    {
        if (!$this->has($key)) {
            return $this;
        }
        $clone = clone $this;
        unset($clone->parameters[$key]);

        return $clone;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->all();
    }
}
