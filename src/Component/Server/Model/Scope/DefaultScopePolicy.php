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

namespace OAuth2Framework\Component\Server\Model\Scope;

use OAuth2Framework\Component\Server\Model\Client\Client;

final class DefaultScopePolicy implements ScopePolicyInterface
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
    public function checkScopePolicy(array $scope, Client $client): array
    {
        return ($client->has('default_scope')) && null !== $client->get('default_scope') ? $client->get('default_scope') : $this->getDefaultScopes();
    }

    /**
     * @return string[]
     */
    private function getDefaultScopes(): array
    {
        return $this->defaultScopes;
    }
}
