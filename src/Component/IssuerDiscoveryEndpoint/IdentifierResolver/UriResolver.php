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

namespace OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver;

use function League\Uri\parse;

class UriResolver implements IdentifierResolver
{
    /**
     * @param string $resource_name
     *
     * @return bool
     */
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return 'https' === $uri['scheme'] && null !== $uri['user'];
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        if (!is_string($uri['user']) || !is_string($uri['host'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return new Identifier($uri['user'], $uri['host'], $uri['port']);
    }
}
