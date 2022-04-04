<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

interface TokenTypeHint
{
    public function hint(): string;

    public function find(string $token, ?ResourceServerId $resourceServerId): mixed;

    public function introspect(mixed $token): array;
}
