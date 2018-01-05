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

namespace OAuth2Framework\Component\Server\Core\Scope;

use OAuth2Framework\Component\Server\Core\Client\Client;

final class ErrorScopePolicy implements ScopePolicy
{
    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'error';
    }

    /**
     * {@inheritdoc}
     */
    public function applyScopePolicy(array $scope, Client $client): array
    {
        throw new \RuntimeException('No scope was requested.');
    }
}
