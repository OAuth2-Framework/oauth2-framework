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

namespace OAuth2Framework\Component\TokenEndpoint;

use function Safe\sprintf;
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
        return \array_key_exists($name, $this->grantTypes);
    }

    public function get(string $name): GrantType
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('The grant type "%s" is not supported.', $name));
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
