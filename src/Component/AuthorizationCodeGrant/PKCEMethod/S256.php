<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

use Base64Url\Base64Url;

final class S256 implements PKCEMethod
{
    public function name(): string
    {
        return 'S256';
    }

    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool
    {
        return hash_equals($codeChallenge, Base64Url::encode(hash('sha256', $codeVerifier, true)));
    }
}
