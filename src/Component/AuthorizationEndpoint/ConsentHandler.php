<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ConsentHandler
{
    public function handle(ServerRequestInterface $request, string $authorizationId): ResponseInterface;
}
