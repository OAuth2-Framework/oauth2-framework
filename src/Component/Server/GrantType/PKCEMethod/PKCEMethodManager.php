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

namespace OAuth2Framework\Component\Server\GrantType\PKCEMethod;

final class PKCEMethodManager
{
    /**
     * @var PKCEMethodInterface[]
     */
    private $pkceMethods = [];

    /**
     * @param PKCEMethodInterface $method
     *
     * @return PKCEMethodManager
     */
    public function add(PKCEMethodInterface $method): PKCEMethodManager
    {
        $this->pkceMethods[$method->getMethodName()] = $method;

        return $this;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function has(string $method): bool
    {
        return array_key_exists($method, $this->pkceMethods);
    }

    /**
     * @param string $method
     *
     * @throws \InvalidArgumentException
     *
     * @return PKCEMethodInterface
     */
    public function get(string $method): PKCEMethodInterface
    {
        return $this->pkceMethods[$method];
    }

    /**
     * @return PKCEMethodInterface[]
     */
    public function all(): array
    {
        return array_values($this->pkceMethods);
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_keys($this->pkceMethods);
    }
}
