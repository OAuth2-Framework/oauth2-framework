<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\MetadataEndpoint;

use function Safe\sprintf;
class Metadata
{
    private array $values = [];

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    /**
     * @return null|mixed
     */
    public function get(string $key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('The value with key "%s" does not exist.', $key));
        }

        return $this->values[$key];
    }

    /**
     * @param null|mixed $value
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
