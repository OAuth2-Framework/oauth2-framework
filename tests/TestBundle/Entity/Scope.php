<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Scope\Scope as ScopeInterface;
use Stringable;

final class Scope implements ScopeInterface, Stringable
{
    public function __construct(
        private string $name,
        private ?string $parent = null,
        private bool $isParentMandatory = false
    ) {
    }

    public function __toString(): string
    {
        return $this->getName();
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
}