<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use function count;
use InvalidArgumentException;
use League\Uri\Uri;

final class AccountResolver implements IdentifierResolver
{
    public static function create(): static
    {
        return new self();
    }

    public function supports(string $resource): bool
    {
        return Uri::createFromString($resource)->getScheme() === 'acct';
    }

    public function resolve(string $resource): Identifier
    {
        $uri = Uri::createFromString($resource);
        if ($uri->getPath() === '') {
            throw new InvalidArgumentException('Invalid resource.');
        }
        $parts = explode('@', $uri->getPath());
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid resource.');
        }

        $parts[0] = str_replace('%40', '@', $parts[0]);

        $pos = mb_strpos($parts[1], ':');
        if ($pos === false) {
            $port = null;
        } else {
            $port = (int) mb_substr($parts[1], $pos + 1);
            $parts[1] = mb_substr($parts[1], 0, $pos);
        }

        return Identifier::create($parts[0], $parts[1], $port);
    }
}
