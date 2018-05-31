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

final class EmailResolver implements IdentifierResolver
{
    /**
     * @param string $resource_name
     *
     * @return bool
     */
    public function supports(string $resource_name): bool
    {
        $uri = parse('http://'.$resource_name);

        return 'http' === $uri['scheme'] && null !== $uri['user'] && null !== $uri['host'] && '' === $uri['path'] && null === $uri['query'] && null === $uri['fragment'];
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $resource_name): Identifier
    {
        $uri = parse('http://'.$resource_name);
        if (!is_string($uri['user']) || !is_string($uri['host'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return new Identifier($uri['user'], $uri['host'], $uri['port']);
    }
}
