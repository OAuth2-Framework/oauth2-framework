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

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

interface ScopePolicy
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * This function check if the scopes respect the scope policy for the client.
     *
     * @param string $scope  The scopes. This variable may be modified according to the scope policy
     * @param Client $client The client
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function applyScopePolicy(string $scope, Client $client): string;
}
