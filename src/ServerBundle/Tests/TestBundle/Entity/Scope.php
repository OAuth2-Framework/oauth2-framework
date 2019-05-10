<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Scope\Scope as ScopeInterface;

final class Scope implements ScopeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $parent;

    /**
     * @var bool
     */
    private $isParentMandatory;

    /**
     * Scope constructor.
     */
    public function __construct(string $name, string $parent = null, bool $isParentMandatory = false)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->isParentMandatory = $isParentMandatory;
    }

    public function parent(): ?string
    {
        return $this->parent;
    }

    public function isParentMandatory(): bool
    {
        return $this->isParentMandatory;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name();
    }

    public function jsonSerialize()
    {
        return $this->name();
    }
}
