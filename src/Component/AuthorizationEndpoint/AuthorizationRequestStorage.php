<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;

interface AuthorizationRequestStorage
{
    public function generateId(): string;

    public function getId(ServerRequestInterface $request): string;

    public function has(string $authorizationId): bool;

    public function get(string $authorizationId): AuthorizationRequest;

    public function set(string $authorizationId, AuthorizationRequest $authorization): void;

    public function remove(string $authorizationId): void;
}
