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

namespace OAuth2Framework\Component\Server\ResponseMode;

use Assert\Assertion;

final class ResponseModeManager
{
    /**
     * @var ResponseModeInterface[]
     */
    private $responseModes = [];

    /**
     * @return string[]
     */
    public function getSupportedResponseModes(): array
    {
        return array_keys($this->responseModes);
    }

    /**
     * @param ResponseModeInterface $responseMode
     *
     * @return ResponseModeManager
     */
    public function add(ResponseModeInterface $responseMode)
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
     * @return ResponseModeInterface
     */
    public function get(string $name): ResponseModeInterface
    {
        Assertion::true($this->has($name), sprintf('The response mode with name \'%s\' is not supported.', $name));

        return $this->responseModes[$name];
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->responseModes);
    }
}
