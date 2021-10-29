<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant\PKCEMethod;

interface PKCEMethod
{
    public function name(): string;

    public function isChallengeVerified(string $codeVerifier, string $codeChallenge): bool;
}
