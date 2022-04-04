<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use Assert\Assertion;
use function League\Uri\parse;

final class UriResolver implements IdentifierResolver
{
    public static function create(): static
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        $uri = parse($resource);

        return $uri['scheme'] === 'https' && $uri['user'] !== null;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse($resource);
        Assertion::string($uri['user'], 'Invalid resource.');
        Assertion::string($uri['host'], 'Invalid resource.');

        return Identifier::create($uri['user'], $uri['host'], $uri['port']);
    }
}
