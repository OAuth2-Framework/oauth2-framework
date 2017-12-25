<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Core\Scope;

use OAuth2Framework\Component\Server\Core\Client\Client;

final class NoScopePolicy implements ScopePolicy
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'none';
    }

    /**
     * {@inheritdoc}
     */
    public function applyScopePolicy(array $scope, Client $client): array
    {
        return $scope;
    }
}
