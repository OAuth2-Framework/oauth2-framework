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

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

final class ErrorScopePolicy implements ScopePolicy
{
    public function name(): string
    {
        return 'error';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        throw new \RuntimeException('No scope was requested.');
    }
}
