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

namespace OAuth2Framework\Component\MetadataEndpoint;

class Metadata implements \JsonSerializable
{
    /**
     * @var array
     */
    private $values = [];

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The value with key "%s" does not exist.', $key));
        }

        return $this->values[$key];
    }

    public function set(string $key, $value)
    {
        $this->values[$key] = $value;
    }

    public function jsonSerialize()
    {
        return $this->values;
    }
}
