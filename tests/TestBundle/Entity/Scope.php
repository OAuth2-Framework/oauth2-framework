<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Scope\Scope as ScopeInterface;
use Stringable;

final class Scope implements ScopeInterface, Stringable
{
    public function __construct(
        private readonly string $name,
        private readonly ?string $parent = null,
        private readonly bool $isParentMandatory = false
    ) {
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public static function create(string $name, ?string $parent = null, bool $isParentMandatory = false): self
    {
        return new self($name, $parent, $isParentMandatory);
    }

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function isParentMandatory(): bool
    {
        return $this->isParentMandatory;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): string
    {
        return $this->name;
    }
}
