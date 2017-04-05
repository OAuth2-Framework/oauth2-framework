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

namespace OAuth2Framework\Component\Server\Endpoint\Token\Processor;

use OAuth2Framework\Component\Server\Endpoint\Token\GrantTypeData;
use OAuth2Framework\Component\Server\GrantType\GrantTypeInterface;
use OAuth2Framework\Component\Server\Model\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Model\Scope\ScopeRepositoryInterface;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class ScopeProcessor
{
    /**
     * @var ScopeRepositoryInterface
     */
    private $scopeRepository;

    /**
     * @var ScopePolicyManager|null
     */
    private $scopePolicyManager;

    /**
     * ScopeProcessor constructor.
     *
     * @param ScopeRepositoryInterface $scopeRepository
     * @param ScopePolicyManager|null  $scopePolicyManager
     */
    public function __construct(ScopeRepositoryInterface $scopeRepository, ? ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantTypeInterface     $grantType
     * @param callable               $next
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
     */
    public function __invoke(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantTypeInterface $grantType, callable $next): GrantTypeData
    {
        $params = $request->getParsedBody() ?? [];
        if (!array_key_exists('scope', $params)) {
            return $next($request, $grantTypeData, $grantType);
        }
        $scopeParameter = $params['scope'];
        $scope = $this->scopeRepository->convertToArray($scopeParameter);

        //Modify the scope according to the scope policy
        try {
            if (null !== $this->scopePolicyManager) {
                $scope = $this->scopePolicyManager->check($scope, $grantTypeData->getClient());
            }
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE,
                    'error_description' => $e->getMessage(), ]
            );
        }

        $availableScope = is_array($grantTypeData->getAvailableScopes()) ? $grantTypeData->getAvailableScopes() : $this->scopeRepository->getAvailableScopesForClient($grantTypeData->getClient());

        //Check if scope requested are within the available scope
        if (!$this->scopeRepository->areRequestedScopesAvailable($scope, $availableScope)) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_SCOPE,
                    'error_description' => sprintf('An unsupported scope was requested. Available scopes are %s.', implode(', ', $availableScope)),
                ]
            );
        }

        $grantTypeData = $grantTypeData->withScopes($scope);

        return $next($request, $grantTypeData, $grantType);
    }
}
