<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use InvalidArgumentException;

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

        throw new InvalidArgumentException('Resource not supported.');
    }
}
