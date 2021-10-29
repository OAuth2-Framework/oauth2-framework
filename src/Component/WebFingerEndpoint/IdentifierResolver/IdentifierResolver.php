<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

interface IdentifierResolver
{
    public function supports(string $resource): bool;

    public function resolve(string $resource): Identifier;
}
