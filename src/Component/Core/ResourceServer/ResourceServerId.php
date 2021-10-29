<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\ResourceServer;

use Stringable;

class ResourceServerId implements Stringable
{
    public function __construct(
        private string $value
    ) {
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
