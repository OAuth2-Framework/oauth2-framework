<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Client;

use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;

class ClientId extends ResourceOwnerId
{
    public static function create(string $value): static
    {
        return new self($value);
    }
}
