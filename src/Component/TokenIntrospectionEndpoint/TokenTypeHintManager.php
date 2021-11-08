<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

class TokenTypeHintManager
{
    /**
     * @var TokenTypeHint[]
     */
    private array $tokenTypeHints = [];

    public static function create(): self
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

    public function add(TokenTypeHint $tokenTypeHint): self
    {
        $this->tokenTypeHints[$tokenTypeHint->hint()] = $tokenTypeHint;

        return $this;
    }
}
