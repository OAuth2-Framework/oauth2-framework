<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

interface TokenTypeHint
{
    public function hint(): string;

    public function find(string $token): mixed;

    public function revoke(mixed $token): void;
}
