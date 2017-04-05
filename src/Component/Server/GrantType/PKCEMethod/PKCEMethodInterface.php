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

interface PKCEMethodInterface
{
    /**
     * @return string
     */
    public function getMethodName(): string;

    /**
     * @param string $codeVerifier
     * @param string $codeChallenge
     *
     * @return bool
     */
    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool;
}
