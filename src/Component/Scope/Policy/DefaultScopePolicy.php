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

class DefaultScopePolicy implements ScopePolicy
{
    /**
     * @var string
     */
    private $defaultScopes;

    /**
     * DefaultScopePolicy constructor.
     *
     * @param string $defaultScopes
     */
    public function __construct(string $defaultScopes)
    {
        $this->defaultScopes = $defaultScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    public function applyScopePolicy(string $scope, Client $client): string
    {
        return $client->has('default_scope') ? $client->get('default_scope') : $this->getDefaultScopes();
    }

    /**
     * @return string
     */
    private function getDefaultScopes(): string
    {
        return $this->defaultScopes;
    }
}
