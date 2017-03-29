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

final class Plain implements PKCEMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMethodName(): string
    {
        return 'plain';
    }

    /**
     * {@inheritdoc}
     */
    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool
    {
        return hash_equals($codeChallenge, $codeVerifier);
    }
}
