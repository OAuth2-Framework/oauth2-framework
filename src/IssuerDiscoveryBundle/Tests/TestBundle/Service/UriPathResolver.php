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

namespace OAuth2Framework\IssuerDiscoveryBundle\Tests\TestBundle\Service;

use function League\Uri\parse;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\IssuerDiscoveryEndpoint\IdentifierResolver\IdentifierResolver;

class UriPathResolver implements IdentifierResolver
{
    /**
     * @param string $resource_name
     *
     * @return bool
     */
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return 'https' === $uri['scheme']
            && null === $uri['user']
            && null !== $uri['path']
            && '/+' === substr($uri['path'], 0, 2)
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        if (!is_string($uri['path']) || !is_string($uri['host'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return new Identifier(substr($uri['path'], 2), $uri['host'], $uri['port']);
    }
}
