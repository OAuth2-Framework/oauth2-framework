<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Scope;

interface Scope extends \JsonSerializable
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @return string
     */
    public function __toString(): string;
}
