<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use Assert\Assertion;
use function League\Uri\parse;

final class UriResolver implements IdentifierResolver
{
    public function supports(string $resource): bool
    {
        $uri = parse($resource);

        return 'https' === $uri['scheme'] && null !== $uri['user'];
    }

    public function resolve(string $resource): Identifier
    {
        $uri = parse($resource);
        Assertion::string($uri['user'], 'Invalid resource.');
        Assertion::string($uri['host'], 'Invalid resource.');

        return new Identifier($uri['user'], $uri['host'], $uri['port']);
    }
}
