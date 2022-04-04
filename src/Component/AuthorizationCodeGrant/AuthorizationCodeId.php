<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

use Stringable;

final class AuthorizationCodeId implements Stringable
{
    public function __construct(
        private string $value
    ) {
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public static function create(string $value): static
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
