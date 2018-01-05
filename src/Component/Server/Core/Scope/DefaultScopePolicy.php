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

final class DefaultScopePolicy implements ScopePolicy
{
    /**
     * @var string[]
     */
    private $defaultScopes;

    /**
     * DefaultScopePolicy constructor.
     *
     * @param string[] $defaultScopes
     */
    public function __construct(array $defaultScopes)
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
    public function applyScopePolicy(array $scope, Client $client): array
    {
        return $client->has('default_scope') ? explode(' ', $client->get('default_scope')) : $this->getDefaultScopes();
    }

    /**
     * @return string[]
     */
    private function getDefaultScopes(): array
    {
        return $this->defaultScopes;
    }
}
