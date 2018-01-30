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

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

use Base64Url\Base64Url;

final class S256 implements PKCEMethod
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'S256';
    }

    /**
     * {@inheritdoc}
     */
    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool
    {
        return hash_equals($codeChallenge, Base64Url::encode(hash('sha256', $codeVerifier, true)));
    }
}
