<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver;

use InvalidArgumentException;

final class IdentifierResolverManager
{
    /**
     * @var IdentifierResolver[]
     */
    private array $resolvers = [];

    public static function create(): static
    {
        return new self();
    }

    public function add(IdentifierResolver $resolver): static
    {
        $this->resolvers[] = $resolver;

        return $this;
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
