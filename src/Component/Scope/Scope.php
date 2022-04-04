<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope;

use JsonSerializable;

interface Scope extends JsonSerializable
{
    public function __toString(): string;

    public function getName(): string;

    public function getParent(): ?string;

    public function isParentMandatory(): bool;
}
