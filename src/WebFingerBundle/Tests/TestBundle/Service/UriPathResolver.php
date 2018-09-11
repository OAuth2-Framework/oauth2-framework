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

namespace OAuth2Framework\WebFingerBundle\Tests\TestBundle\Service;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;
use function League\Uri\parse;

class UriPathResolver implements IdentifierResolver
{
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return 'https' === $uri['scheme']
            && null === $uri['user']
            && null !== $uri['path']
            && '/+' === \mb_substr($uri['path'], 0, 2)
            ;
    }

    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        if (!\is_string($uri['path']) || !\is_string($uri['host'])) {
            throw new \InvalidArgumentException('Invalid resource.');
        }

        return new Identifier(\mb_substr($uri['path'], 2), $uri['host'], $uri['port']);
    }
}
