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

class AccountResolver implements IdentifierResolver
{
    /**
     * @param string $resource_name
     *
     * @return bool
     */
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return 'acct' === $uri['scheme'];
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        if (!is_string($uri['path'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }
        $parts = explode('@', $uri['path']);
        if (2 !== count($parts)) {
            throw new \InvalidArgumentException('Invalid resource.');
        }
        $parts[0] = str_replace('%40', '@', $parts[0]);
        $pos = strpos($parts[1], ':');
        if (false === $pos) {
            $port = null;
        } else {
            $port = intval(substr($parts[1], $pos + 1));
            $parts[1] = substr($parts[1], 0, $pos);
        }

        return new Identifier($parts[0], $parts[1], $port);
    }
}
