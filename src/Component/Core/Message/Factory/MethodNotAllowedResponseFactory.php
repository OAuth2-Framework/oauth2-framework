<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\Message\Factory;

final class MethodNotAllowedResponseFactory extends OAuth2ResponseFactory
{
    public function getSupportedCode(): int
    {
        return 405;
    }
}
