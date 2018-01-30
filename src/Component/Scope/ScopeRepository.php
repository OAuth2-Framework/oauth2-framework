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

namespace OAuth2Framework\Component\Scope;

interface ScopeRepository
{
    /**
     * @param string $scope
     *
     * @return bool
     */
    public function has(string $scope): bool;

    /**
     * @param string $scope
     *
     * @return Scope
     */
    public function get(string $scope): Scope;

    /**
     * @return Scope[]
     */
    public function all(): array;
}
