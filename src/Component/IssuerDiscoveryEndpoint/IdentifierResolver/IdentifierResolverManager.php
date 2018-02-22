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

class IdentifierResolverManager
{
    /**
     * @var IdentifierResolver[]
     */
    private $resolvers = [];

    /**
     * @param IdentifierResolver $resolver
     */
    public function add(IdentifierResolver $resolver)
    {
        $this->resolvers[] = $resolver;
    }

    /**
     * @param string $resource
     *
     * @return Identifier
     */
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
