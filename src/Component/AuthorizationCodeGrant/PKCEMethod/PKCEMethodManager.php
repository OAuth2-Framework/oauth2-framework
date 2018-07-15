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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

class PKCEMethodManager
{
    /**
     * @var PKCEMethod[]
     */
    private $pkceMethods = [];

    /**
     * @param PKCEMethod $method
     *
     * @return PKCEMethodManager
     */
    public function add(PKCEMethod $method): self
    {
        $this->pkceMethods[$method->name()] = $method;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function has(string $method): bool
    {
        return \array_key_exists($method, $this->pkceMethods);
    }

    /**
     * @param string $method
     *
     * @throws \InvalidArgumentException
     *
     * @return PKCEMethod
     */
    public function get(string $method): PKCEMethod
    {
        return $this->pkceMethods[$method];
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return \array_keys($this->pkceMethods);
    }
}
