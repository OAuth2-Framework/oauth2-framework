<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use function League\Uri\parse;

final class EmailResolver implements IdentifierResolver
{
    public function supports(string $resource): bool
    {
        $uri = parse('http://'.$resource);

        return 'http' === $uri['scheme'] && null !== $uri['user'] && null !== $uri['host'] && '' === $uri['path'] && null === $uri['query'] && null === $uri['fragment'];
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse('http://'.$resource);
        if (!\is_string($uri['user']) || !\is_string($uri['host'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return new Identifier($uri['user'], $uri['host'], $uri['port']);
    }
}
