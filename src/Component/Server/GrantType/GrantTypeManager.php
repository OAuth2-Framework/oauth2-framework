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

namespace OAuth2Framework\Component\Server\GrantType;

use Assert\Assertion;

final class GrantTypeManager
{
    /**
     * @var GrantTypeInterface[]
     */
    private $grantTypes = [];

    /**
     * @param GrantTypeInterface $grantType
     *
     * @return GrantTypeManager
     */
    public function add(GrantTypeInterface $grantType): GrantTypeManager
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
     * @return GrantTypeInterface
     */
    public function get(string $names): GrantTypeInterface
    {
        Assertion::true($this->has($names), sprintf('The grant type \'%s\' is not supported.', $names));

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
