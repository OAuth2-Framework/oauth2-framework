<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use InvalidArgumentException;
use function is_string;
use function League\Uri\parse;

final class EmailResolver implements IdentifierResolver
{
    public static function create(): static
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        $uri = parse('https://' . $resource);

        return $uri['scheme'] === 'https' && $uri['user'] !== null && $uri['pass'] === null && $uri['host'] !== null && $uri['path'] === '' && $uri['query'] === null && $uri['fragment'] === null;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse('https://' . $resource);
        if (! is_string($uri['user']) || ! is_string($uri['host'])) {
            throw new InvalidArgumentException('Invalid resource.');
        }

        return Identifier::create($uri['user'], $uri['host'], $uri['port']);
    }
}
