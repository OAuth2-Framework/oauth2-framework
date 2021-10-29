<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use InvalidArgumentException;
use function is_string;
use function League\Uri\parse;

final class EmailResolver implements IdentifierResolver
{
    public function supports(string $resource): bool
    {
        $uri = parse('http://' . $resource);

        return $uri['scheme'] === 'http' && $uri['user'] !== null && $uri['host'] !== null && $uri['path'] === '' && $uri['query'] === null && $uri['fragment'] === null;
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse('http://' . $resource);
        if (! is_string($uri['user']) || ! is_string($uri['host'])) {
            throw new InvalidArgumentException('Invalid resource.');
        }

        return new Identifier($uri['user'], $uri['host'], $uri['port']);
    }
}
