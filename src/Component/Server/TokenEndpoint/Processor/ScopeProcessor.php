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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Processor;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\Core\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Core\Scope\ScopeRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class ScopeProcessor
{
    /**
     * @var ScopeRepository
     */
    private $scopeRepository;

    /**
     * @var ScopePolicyManager|null
     */
    private $scopePolicyManager;

    /**
     * ScopeProcessor constructor.
     *
     * @param ScopeRepository         $scopeRepository
     * @param ScopePolicyManager|null $scopePolicyManager
     */
    public function __construct(ScopeRepository $scopeRepository, ? ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        /** @var GrantTypeData $grantTypeData */
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $params = $request->getParsedBody() ?? [];
        if (!array_key_exists('scope', $params)) {
            $scope = $grantTypeData->getAvailableScopes() ?? [];
        } else {
            $scopeParameter = $params['scope'];
            $scope = explode(' ', $scopeParameter);
        }

        //Modify the scope according to the scope policy
        try {
            if (null !== $this->scopePolicyManager) {
                $scope = $this->scopePolicyManager->apply($scope, $grantTypeData->getClient());
            }
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_SCOPE, $e->getMessage(), [], $e);
        }

        $availableScope = $grantTypeData->getAvailableScopes() ? $grantTypeData->getAvailableScopes() : $this->scopeRepository->getAvailableScopesForClient($grantTypeData->getClient());

        //Check if requested scope are within the available scope
        if (!$this->scopeRepository->areRequestedScopesAvailable($scope, $availableScope)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_SCOPE, sprintf('An unsupported scope was requested. Available scopes are %s.', implode(', ', $availableScope)));
        }

        $grantTypeData = $grantTypeData->withScopes($scope);

        return $grantTypeData;
    }
}
