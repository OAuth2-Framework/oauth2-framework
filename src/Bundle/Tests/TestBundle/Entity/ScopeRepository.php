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

namespace OAuth2Framework\Bundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Scope\Scope as ScopeInterface;
use OAuth2Framework\Component\Scope\ScopeRepository as ScopeRepositoryInterface;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeInterface[]
     */
    private $scopes = [];

    /**
     * ScopeRepository constructor.
     */
    public function __construct()
    {
        $this->scopes['openid'] = new Scope('openid');
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $scope): bool
    {
        return array_key_exists($scope, $this->scopes);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $scope): ScopeInterface
    {
        if (!$this->has($scope)) {
            throw new \InvalidArgumentException(sprintf('The scope "%s" is not supported.', $scope));
        }

        return $this->scopes[$scope];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return array_values($this->scopes);
    }
}