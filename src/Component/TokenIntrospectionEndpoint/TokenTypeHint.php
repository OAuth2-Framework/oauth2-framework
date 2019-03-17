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

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

interface TokenTypeHint
{
    public function hint(): string;

    /**
     * @return mixed|null
     */
    public function find(string $token);

    /**
     * @param mixed $token
     */
    public function introspect($token): array;
}
