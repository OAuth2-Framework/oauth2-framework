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

namespace OAuth2Framework\Component\Server\TokenRevocationEndpoint;

use OAuth2Framework\Component\Server\Core\Token\Token;

interface TokenTypeHint
{
    /**
     * @return string
     */
    public function hint(): string;

    /**
     * @param string $token
     *
     * @return null|Token
     */
    public function find(string $token): ?Token;

    /**
     * @param Token $token
     */
    public function revoke(Token $token);
}
