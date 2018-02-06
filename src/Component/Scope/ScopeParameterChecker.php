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

namespace OAuth2Framework\Component\Scope;

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;

class ScopeParameterChecker implements ParameterChecker
{
    /**
     * @var ScopeRepository
     */
    private $scopeRepository;

    /**
     * @var ScopePolicyManager
     */
    private $scopePolicyManager;

    /**
     * ScopeParameterChecker constructor.
     *
     * @param ScopeRepository    $scopeRepository
     * @param ScopePolicyManager $scopePolicyManager
     */
    public function __construct(ScopeRepository $scopeRepository, ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization): Authorization
    {
        try {
            if ($authorization->hasQueryParam('scope')) {
                $requestedScope = $authorization->getQueryParam('scope');
                if (1 !== preg_match('/^[\x20\x23-\x5B\x5D-\x7E]+$/', $requestedScope)) {
                    throw new \InvalidArgumentException('Invalid characters found in the "scope" parameter.');
                }
            } else {
                $requestedScope = '';
            }
            $requestedScope = $this->scopePolicyManager->apply($requestedScope, $authorization->getClient());
            $scopes = explode(' ', $requestedScope);

            $availableScope = $this->scopeRepository->getAvailableScopesForClient($authorization->getClient());
            if (!$this->scopeRepository->areRequestedScopesAvailable($scopes, $availableScope)) {
                throw new \InvalidArgumentException(sprintf('An unsupported scope was requested. Available scopes for the client are %s.', implode(', ', $availableScope)));
            }
            $authorization = $authorization->withScopes($scope);

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_SCOPE, $e->getMessage(), $e);
        }
    }
}
