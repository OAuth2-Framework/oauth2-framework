<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface AuthorizationEndpointHook
{
    public function handle(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ?ResponseInterface;
}
