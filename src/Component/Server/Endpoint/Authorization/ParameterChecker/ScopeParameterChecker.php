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

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\ParameterChecker;

use Assert\Assertion;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;

final class ScopeParameterChecker implements ParameterCheckerInterface
{
    /**
     * @var ScopeRepositoryInterface
     */
    private $scopeRepository;

    /**
     * @var null|ScopePolicyManager
     */
    private $scopePolicyManager;

    /**
     * ScopeParameterChecker constructor.
     *
     * @param ScopeRepositoryInterface $scopeRepository
     * @param null|ScopePolicyManager  $scopePolicyManager
     */
    public function __construct(ScopeRepositoryInterface $scopeRepository, ? ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Authorization $authorization, callable $next): Authorization
    {
        try {
            if ($authorization->hasQueryParam('scope')) {
                Assertion::regex($authorization->getQueryParam('scope'), '/^[\x20\x23-\x5B\x5D-\x7E]+$/', 'Invalid characters found in the \'scope\' parameter.');
                $scope = explode(' ', $authorization->getQueryParam('scope'));
            } else {
                $scope = [];
            }
            if (null !== $this->scopePolicyManager) {
                $scope = $this->scopePolicyManager->check($scope, $authorization->getClient());
            }
            $availableScope = $this->scopeRepository->getAvailableScopesForClient($authorization->getClient());
            Assertion::true($this->scopeRepository->areRequestedScopesAvailable($scope, $availableScope), sprintf('An unsupported scope was requested. Available scopes for the client are %s.', implode(', ', $availableScope)));
            $authorization = $authorization->withScopes($scope);

            return $next($authorization);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, ['error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE, 'error_description' => $e->getMessage(), 'authorization' => $authorization]);
        }
    }
}
