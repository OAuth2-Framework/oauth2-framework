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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

class ResponseModeManager
{
    /**
     * @var ResponseMode[]
     */
    private $responseModes = [];

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->responseModes);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_values($this->responseModes);
    }

    /**
     * @param ResponseMode $responseMode
     *
     * @return ResponseModeManager
     */
    public function add(ResponseMode $responseMode)
    {
        $this->responseModes[$responseMode->name()] = $responseMode;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->responseModes);
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseMode
     */
    public function get(string $name): ResponseMode
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('The response mode with name "%s" is not supported.', $name));
        }

        return $this->responseModes[$name];
    }
}
