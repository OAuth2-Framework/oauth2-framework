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

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;

final class ScopePolicyManager
{
    /**
     * @var ScopePolicyInterface[]
     */
    private $scopePolicies = [];

    /**
     * @var string
     */
    private $defaultScopePolicy = null;

    /**
     * @param ScopePolicyInterface $scopePolicy
     * @param bool                 $isDefault
     *
     * @return ScopePolicyManager
     */
    public function add(ScopePolicyInterface $scopePolicy, bool $isDefault = false): ScopePolicyManager
    {
        $name = $scopePolicy->name();
        $this->scopePolicies[$name] = $scopePolicy;

        if (true === $isDefault || 1 === count($this->scopePolicies)) {
            $this->defaultScopePolicy = $name;
        }

        return $this;
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
     * @return ScopePolicyInterface
     */
    public function get(string $scopePolicyName): ScopePolicyInterface
    {
        Assertion::keyExists($this->scopePolicies, $scopePolicyName, sprintf('The scope policy with name \'%s\' is not supported', $scopePolicyName));

        return $this->scopePolicies[$scopePolicyName];
    }

    /**
     * @return ScopePolicyInterface|null
     */
    public function default(): ?ScopePolicyInterface
    {
        if (null === $this->defaultScopePolicy) {
            return null;
        }

        return $this->scopePolicies[$this->defaultScopePolicy];
    }

    /**
     * @param array  $scope
     * @param Client $client
     *
     * @return array
     */
    public function check(array $scope, Client $client): array
    {
        if (empty($scope)) {
            $policy = $this->getForClient($client);

            if (null !== $policy) {
                return $policy->checkScopePolicy($scope, $client);
            }
        }

        return $scope;
    }

    /**
     * @param Client $client
     *
     * @return ScopePolicyInterface|null
     */
    private function getForClient(Client $client): ?ScopePolicyInterface
    {
        if ($client->has('scope_policy') && $this->has($client->get('scope_policy'))) {
            $policyName = $client->get('scope_policy');

            return $this->get($policyName);
        }

        return $this->default();
    }
}
