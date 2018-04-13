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

class TokenTypeHintManager
{
    /**
     * @var TokenTypeHint[]
     */
    private $tokenTypeHints = [];

    /**
     * @return TokenTypeHint[]
     */
    public function getTokenTypeHints(): array
    {
        return $this->tokenTypeHints;
    }

    /**
     * @param TokenTypeHint $tokenTypeHint
     *
     * @return TokenTypeHintManager
     */
    public function add(TokenTypeHint $tokenTypeHint): self
    {
        $this->tokenTypeHints[$tokenTypeHint->hint()] = $tokenTypeHint;

        return $this;
    }
}
