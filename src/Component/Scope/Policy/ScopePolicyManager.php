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

class ScopePolicyManager
{
    /**
     * @var ScopePolicy[]
     */
    private $scopePolicies = [];

    /**
     * @var string|null
     */
    private $defaultScopePolicy;

    public function add(ScopePolicy $scopePolicy, bool $isDefault = false): void
    {
        $name = $scopePolicy->name();
        $this->scopePolicies[$name] = $scopePolicy;

        if (true === $isDefault || 1 === \count($this->scopePolicies)) {
            $this->defaultScopePolicy = $name;
        }
    }

    public function apply(string $scope, Client $client): string
    {
        if ('' === $scope) {
            $policy = $this->getForClient($client);

            if (null !== $policy) {
                return $policy->applyScopePolicy($scope, $client);
            }
        }

        return $scope;
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return \array_keys($this->scopePolicies);
    }

    public function has(string $scopePolicy): bool
    {
        return \array_key_exists($scopePolicy, $this->scopePolicies);
    }

    private function get(string $scopePolicyName): ScopePolicy
    {
        if (!$this->has($scopePolicyName)) {
            throw new \InvalidArgumentException(\Safe\sprintf('The scope policy with name "%s" is not supported', $scopePolicyName));
        }

        return $this->scopePolicies[$scopePolicyName];
    }

    private function default(): ?ScopePolicy
    {
        if (null === $this->defaultScopePolicy) {
            return null;
        }

        return $this->scopePolicies[$this->defaultScopePolicy];
    }

    private function getForClient(Client $client): ?ScopePolicy
    {
        if ($client->has('scope_policy') && $this->has($client->get('scope_policy'))) {
            $policyName = $client->get('scope_policy');

            return $this->get($policyName);
        }

        return $this->default();
    }
}
