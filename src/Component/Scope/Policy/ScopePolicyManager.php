<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Scope\Policy;

use function array_key_exists;
use function count;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\Client\Client;

class ScopePolicyManager
{
    /**
     * @var ScopePolicy[]
     */
    private array $scopePolicies = [];

    private ?string $defaultScopePolicy = null;

    public function add(ScopePolicy $scopePolicy, bool $isDefault = false): void
    {
        $name = $scopePolicy->name();
        $this->scopePolicies[$name] = $scopePolicy;

        if ($isDefault === true || count($this->scopePolicies) === 1) {
            $this->defaultScopePolicy = $name;
        }
    }

    public function apply(string $scope, Client $client): string
    {
        if ($scope === '') {
            $policy = $this->getForClient($client);

            if ($policy !== null) {
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

    public function has(string $scopePolicy): bool
    {
        return array_key_exists($scopePolicy, $this->scopePolicies);
    }

    private function get(string $scopePolicyName): ScopePolicy
    {
        if (! $this->has($scopePolicyName)) {
            throw new InvalidArgumentException(sprintf(
                'The scope policy with name "%s" is not supported',
                $scopePolicyName
            ));
        }

        return $this->scopePolicies[$scopePolicyName];
    }

    private function default(): ?ScopePolicy
    {
        if ($this->defaultScopePolicy === null) {
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
