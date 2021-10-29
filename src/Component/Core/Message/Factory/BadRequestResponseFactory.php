<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

final class BadRequestResponseFactory extends OAuth2ResponseFactory
{
    public function getSupportedCode(): int
    {
        return 400;
    }
}
