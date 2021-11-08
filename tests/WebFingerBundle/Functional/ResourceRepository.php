<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\WebFingerBundle\Functional;

use OAuth2Framework\Component\WebFingerEndpoint\IdentifierResolver\Identifier;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceDescriptor;
use OAuth2Framework\Component\WebFingerEndpoint\ResourceRepository as ResourceRepositoryInterface;

final class ResourceRepository implements ResourceRepositoryInterface
{
    private array $resources = [];

    public static function create(): self
    {
        return new self();
    }

    public function set(string $resource, Identifier $identifier, ResourceDescriptor $descriptor): self
    {
        $this->resources[$resource] = [
            'identifier' => $identifier,
            'descriptor' => $descriptor,
        ];

        return $this;
    }

    public function find(string $resource, Identifier $identifier): ?ResourceDescriptor
    {
        return $this->resources[$resource]['descriptor'] ?? null;
    }
}
