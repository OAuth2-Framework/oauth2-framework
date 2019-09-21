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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Repository;

use OAuth2Framework\Component\Scope\Scope as ScopeInterface;
use OAuth2Framework\Component\Scope\ScopeRepository as ScopeRepositoryInterface;
use OAuth2Framework\ServerBundle\Tests\TestBundle\Entity\Scope;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeInterface[]
     */
    private $scopes = [];

    public function __construct()
    {
        $this->scopes['openid'] = new Scope('openid');
        $this->scopes['scope1'] = new Scope('scope1');
    }

    public function has(string $scope): bool
    {
        return \array_key_exists($scope, $this->scopes);
    }

    public function get(string $scope): ScopeInterface
    {
        if (!$this->has($scope)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The scope "%s" is not supported.', $scope));
        }

        return $this->scopes[$scope];
    }

    public function all(): array
    {
        return array_values($this->scopes);
    }
}
