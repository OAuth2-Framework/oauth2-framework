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

namespace OAuth2Framework\WebFingerBundle\Tests\TestBundle\Service;

use Assert\Assertion;
use function League\Uri\parse;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\IdentifierResolver;

final class UriPathResolver implements IdentifierResolver
{
    public function supports(string $resource_name): bool
    {
        $uri = parse($resource_name);

        return 'https' === $uri['scheme']
            && null === $uri['user']
            && null !== $uri['path']
            && '/+' === mb_substr($uri['path'], 0, 2)
            ;
    }

    public function resolve(string $resource_name): Identifier
    {
        $uri = parse($resource_name);
        Assertion::string($uri['path'], 'Invalid resource.');
        Assertion::string($uri['host'], 'Invalid resource.');

        return new Identifier(mb_substr($uri['path'], 2), $uri['host'], $uri['port']);
    }
}
