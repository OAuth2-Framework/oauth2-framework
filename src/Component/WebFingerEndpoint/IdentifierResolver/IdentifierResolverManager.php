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

class IdentifierResolverManager
{
    /**
     * @var IdentifierResolver[]
     */
    private array $resolvers = [];

    public function add(IdentifierResolver $resolver): void
    {
        $this->resolvers[] = $resolver;
    }

    public function resolve(string $resource): Identifier
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($resource)) {
                return $resolver->resolve($resource);
            }
        }

        throw new \InvalidArgumentException('Resource not supported.');
    }
}
