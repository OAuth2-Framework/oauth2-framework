<?php

declare(strict_types=1);

namespace OAuth2Framework\ServerBundle\TokenType;

use Base64Url\Base64Url;
use OAuth2Framework\Component\MacTokenType\MacToken as Base;

final class MacToken extends Base
{
    public function __construct(
        string $macAlgorithm,
        int $timestampLifetime,
        private int $minLength,
        private int $maxLength
    ) {
        parent::__construct($macAlgorithm, $timestampLifetime);
    }

    protected function generateMacKey(): string
    {
        return Base64Url::encode(random_bytes($this->getMacKeyLength()));
    }

    private function getMacKeyLength(): int
    {
        return random_int($this->minLength, $this->maxLength);
    }
}
