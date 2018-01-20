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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint;

final class ResponseTypeManager
{
    /**
     * @var ResponseType[]
     */
    private $responseTypes = [];

    /**
     * @param ResponseType $responseType
     *
     * @return ResponseTypeManager
     */
    public function add(ResponseType $responseType): self
    {
        $this->responseTypes[$responseType->name()] = $responseType;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->responseTypes);
    }

    /**
     * @param string $names
     *
     * @throws \InvalidArgumentException
     *
     * @return ResponseType[]
     */
    public function find(string $names): array
    {
        if (!$this->isSupported($names)) {
            throw new \InvalidArgumentException(sprintf('The response type "%s" is not supported.', $names));
        }
        $responseTypes = explode(' ', $names);

        $types = [];
        foreach ($responseTypes as $responseType) {
            $type = $this->responseTypes[$responseType];
            $types[] = $type;
        }

        return $types;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        $types = array_keys($this->responseTypes);
        if (in_array('id_token', $types)) {
            if (in_array('code', $types)) {
                $types[] = 'code id_token';
            }
            if (in_array('token', $types)) {
                $types[] = 'id_token token';
            }
            if (in_array('code', $types) && in_array('token', $types)) {
                $types[] = 'code id_token token';
            }
        }
        if (in_array('code', $types) && in_array('token', $types)) {
            $types[] = 'code token';
        }

        return $types;
    }

    /**
     * @param string $responseType
     *
     * @return bool
     */
    public function isSupported(string $responseType): bool
    {
        return in_array($responseType, $this->all());
    }
}
