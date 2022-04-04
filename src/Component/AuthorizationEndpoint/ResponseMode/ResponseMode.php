<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use Psr\Http\Message\ResponseInterface;

interface ResponseMode
{
    public function name(): string;

    public function buildResponse(string $redirectUri, array $data): ResponseInterface;
}
