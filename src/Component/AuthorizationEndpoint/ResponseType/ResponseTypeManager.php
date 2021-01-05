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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;

use Assert\Assertion;
use function Safe\sprintf;

class ResponseTypeManager
{
    /**
     * @var ResponseType[]
     */
    private array $responseTypes = [];

    public function add(ResponseType $responseType): void
    {
        $this->responseTypes[$responseType->name()] = $responseType;
    }

    public function has(string $responseType): bool
    {
        return \array_key_exists($responseType, $this->responseTypes);
    }

    public function get(string $responseType): ResponseType
    {
        Assertion::true($this->has($responseType), sprintf('The response type "%s" is not supported.', $responseType));

        return $this->responseTypes[$responseType];
    }

    /**
     * @return string[]
     */
    public function list(): array
    {
        return array_keys($this->responseTypes);
    }

    /**
     * @return ResponseType[]
     */
    public function all(): array
    {
        return array_values($this->responseTypes);
    }
}
