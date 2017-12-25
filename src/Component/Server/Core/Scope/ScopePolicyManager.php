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

final class ScopePolicyManager
{
    /**
     * @var ScopePolicy[]
     */
    private $scopePolicies = [];

    /**
     * @var string
     */
    private $defaultScopePolicy = null;

    /**
     * @param ScopePolicy $scopePolicy
     * @param bool        $isDefault
     *
     * @return ScopePolicyManager
     */
    public function add(ScopePolicy $scopePolicy, bool $isDefault = false): self
    {
        $name = $scopePolicy->name();
        $this->scopePolicies[$name] = $scopePolicy;

        if (true === $isDefault || 1 === count($this->scopePolicies)) {
            $this->defaultScopePolicy = $name;
        }

        return $this;
    }

    /**
     * @param array  $scope
     * @param Client $client
     *
     * @return array
     */
    public function apply(array $scope, Client $client): array
    {
        if (empty($scope)) {
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
        return array_keys($this->scopePolicies);
    }

    /**
     * @param string $scopePolicy
     *
     * @return bool
     */
    public function has(string $scopePolicy): bool
    {
        return array_key_exists($scopePolicy, $this->scopePolicies);
    }

    /**
     * @param string $scopePolicyName
     *
     * @return ScopePolicy
     */
    private function get(string $scopePolicyName): ScopePolicy
    {
        if (!$this->has($scopePolicyName)) {
            throw new \InvalidArgumentException(sprintf('The scope policy with name "%s" is not supported', $scopePolicyName));
        }

        return $this->scopePolicies[$scopePolicyName];
    }

    /**
     * @return ScopePolicy|null
     */
    private function default(): ?ScopePolicy
    {
        if (null === $this->defaultScopePolicy) {
            return null;
        }

        return $this->scopePolicies[$this->defaultScopePolicy];
    }

    /**
     * @param Client $client
     *
     * @return ScopePolicy|null
     */
    private function getForClient(Client $client): ?ScopePolicy
    {
        if ($client->has('scope_policy') && $this->has($client->get('scope_policy'))) {
            $policyName = $client->get('scope_policy');

            return $this->get($policyName);
        }

        return $this->default();
    }
}
