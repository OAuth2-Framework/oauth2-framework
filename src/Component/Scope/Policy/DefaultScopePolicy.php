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

namespace OAuth2Framework\Component\Scope\Policy;

use OAuth2Framework\Component\Core\Client\Client;

final class DefaultScopePolicy implements ScopePolicy
{
    /**
     * @var string
     */
    private $defaultScopes;

    public function __construct(string $defaultScopes)
    {
        $this->defaultScopes = $defaultScopes;
    }

    public function name(): string
    {
        return 'default';
    }

    public function applyScopePolicy(string $scope, Client $client): string
    {
        return $client->has('default_scope') ? $client->get('default_scope') : $this->getDefaultScopes();
    }

    private function getDefaultScopes(): string
    {
        return $this->defaultScopes;
    }
}
