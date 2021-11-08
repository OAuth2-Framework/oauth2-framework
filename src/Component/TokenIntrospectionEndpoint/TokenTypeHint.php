<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

interface TokenTypeHint
{
    public function hint(): string;

    public function find(string $token): mixed;

    public function introspect(mixed $token): array;
}
