<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

class TokenTypeHintManager
{
    /**
     * @var TokenTypeHint[]
     */
    private array $tokenTypeHints = [];

    public static function create(): static
    {
        return new self();
    }

    /**
     * @return TokenTypeHint[]
     */
    public function getTokenTypeHints(): array
    {
        return $this->tokenTypeHints;
    }

    public function add(TokenTypeHint $tokenTypeHint): static
    {
        $this->tokenTypeHints[$tokenTypeHint->hint()] = $tokenTypeHint;

        return $this;
    }
}
