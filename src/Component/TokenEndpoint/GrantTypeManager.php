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

namespace OAuth2Framework\Component\TokenEndpoint;

class GrantTypeManager
{
    /**
     * @var GrantType[]
     */
    private $grantTypes = [];

    /**
     * @param GrantType $grantType
     *
     * @return GrantTypeManager
     */
    public function add(GrantType $grantType): self
    {
        $this->grantTypes[$grantType->name()] = $grantType;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->grantTypes);
    }

    /**
     * @param string $name
     *
     * @return GrantType
     */
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
