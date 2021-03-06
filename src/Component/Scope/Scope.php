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

namespace OAuth2Framework\Component\Scope;

interface Scope
{
    public function __toString(): string;

    public function getName(): string;

    public function getParent(): ?string;

    public function isParentMandatory(): bool;
}
