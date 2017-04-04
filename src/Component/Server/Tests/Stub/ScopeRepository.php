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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyInterface;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var string[]
     */
    private $availableScopes = [];

    /**
     * @var ScopePolicyInterface[]
     */
    private $scopePolicies = [];

    /**
     * @var string
     */
    private $defaultScopePolicy;

    /**
     * ScopeManager constructor.
     *
     * @param array $availableScopes
     */
    public function __construct(array $availableScopes = [])
    {
        $this->availableScopes = $availableScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopePolicy(string $scopePolicyName): ScopePolicyInterface
    {
        Assertion::keyExists($this->scopePolicies, $scopePolicyName, sprintf('The scope policy with name \'%s\' is not supported', $scopePolicyName));

        return $this->scopePolicies[$scopePolicyName];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedScopes(): array
    {
        return $this->availableScopes;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultScopePolicy(): ScopePolicyInterface
    {
        return $this->scopePolicies[$this->defaultScopePolicy];
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableScopesForClient(Client $client): array
    {
        return ($client->has('scope')) ? $this->convertToArray($client->get('scope')) : $this->getSupportedScopes();
    }

    /**
     * {@inheritdoc}
     */
    public function getScopePolicyForClient(Client $client): ScopePolicyInterface
    {
        if ($client->has('scope_policy') && null !== $policyName = $client->get('scope_policy')) {
            return $this->getScopePolicy($policyName);
        }

        return $this->getDefaultScopePolicy();
    }

    /**
     * {@inheritdoc}
     */
    public function checkScopePolicy(array $scope, Client $client): array
    {
        if (empty($scope)) {
            $policy = $this->getScopePolicyForClient($client);
            $policy->checkScopePolicy($scope, $client);
        }

        return $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function areRequestedScopesAvailable(array $requestedScopes, array $availableScopes): bool
    {
        return 0 === count(array_diff($requestedScopes, $availableScopes));
    }

    /**
     * {@inheritdoc}
     */
    public function convertToArray(string $scopes): array
    {
        $this->checkScopeCharset($scopes);
        $scopes = explode(' ', $scopes);

        foreach ($scopes as $scope) {
            $this->checkScopeUsedOnce($scope, $scopes);
        }

        return $scopes;
    }

    /**
     * @param string $scope
     * @param array  $scopes
     *
     * @throws \OAuth2Framework\Component\Server\Response\OAuth2Exception
     */
    private function checkScopeUsedOnce(string $scope, array $scopes)
    {
        if (1 < count(array_keys($scopes, $scope))) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE, 'error_description' => sprintf('Scope \'%s\' appears more than once.', $scope)]);
        }
    }

    /**
     * @param string $scope
     *
     * @throws \OAuth2Framework\Component\Server\Response\OAuth2Exception
     */
    private function checkScopeCharset(string $scope)
    {
        if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $scope)) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE, 'error_description' => 'Scope contains illegal characters.']);
        }
    }
}
