<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

class TokenTypeHintManager
{
    /**
     * @var TokenTypeHint[]
     */
    private array $tokenTypeHints = [];

    /**
     * @return TokenTypeHint[]
     */
    public function getTokenTypeHints(): array
    {
        return $this->tokenTypeHints;
    }

    public function add(TokenTypeHint $tokenTypeHint): void
    {
        $this->tokenTypeHints[$tokenTypeHint->hint()] = $tokenTypeHint;
    }
}
