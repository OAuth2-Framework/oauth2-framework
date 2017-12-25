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

namespace OAuth2Framework\Component\Server\TokenEndpoint;

final class GrantTypeManager
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
        $this->grantTypes[$grantType->getGrantType()] = $grantType;

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
     * @param string $names
     *
     * @return GrantType
     */
    public function get(string $names): GrantType
    {
        if (!$this->has($names)) {
            throw new \InvalidArgumentException(sprintf('The grant type "%s" is not supported.', $names));
        }

        return $this->grantTypes[$names];
    }

    /**
     * @return string[]
     */
    public function getSupportedGrantTypes(): array
    {
        return array_keys($this->grantTypes);
    }
}
