<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

final class Plain implements PKCEMethod
{
    public static function create(): self
    {
        return new self();
    }

    public function name(): string
    {
        return 'plain';
    }

    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool
    {
        return hash_equals($codeChallenge, $codeVerifier);
    }
}
