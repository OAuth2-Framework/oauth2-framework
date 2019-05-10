<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use function League\Uri\parse;

final class AccountResolver implements IdentifierResolver
{
    public function supports(string $resource): bool
    {
        $uri = parse($resource);

        return 'acct' === $uri['scheme'];
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse($resource);
        if (!\is_string($uri['path'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }
        $parts = \explode('@', $uri['path']);
        if (2 !== \count($parts)) {
            throw new \InvalidArgumentException('Invalid resource.');
        }
        $parts[0] = \str_replace('%40', '@', $parts[0]);
        $pos = \mb_strpos($parts[1], ':');
        if (false === $pos) {
            $port = null;
        } else {
            $port = (int) \mb_substr($parts[1], $pos + 1);
            $parts[1] = \mb_substr($parts[1], 0, $pos);
        }

        return new Identifier($parts[0], $parts[1], $port);
    }
}
