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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Processor;

use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\Core\Scope\ScopePolicyManager;
use OAuth2Framework\Component\Server\Core\Scope\ScopeRepository;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Core\Response\OAuth2ResponseFactoryManager;
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
     * @param ScopeRepository $scopeRepository
     * @param ScopePolicyManager|null  $scopePolicyManager
     */
    public function __construct(ScopeRepository $scopeRepository, ? ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantType     $grantType
     * @param callable               $next
     *
     * @throws OAuth2Exception
     *
     * @return GrantTypeData
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
            $scope = $this->scopeRepository->convertToArray($scopeParameter);
        }

        //Modify the scope according to the scope policy
        try {
            if (null !== $this->scopePolicyManager) {
                $scope = $this->scopePolicyManager->check($scope, $grantTypeData->getClient());
            }
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2Exception::ERROR_INVALID_SCOPE,
                    'error_description' => $e->getMessage(), ]
            );
        }

        $availableScope = is_array($grantTypeData->getAvailableScopes()) ? $grantTypeData->getAvailableScopes() : $this->scopeRepository->getAvailableScopesForClient($grantTypeData->getClient());

        //Check if scope requested are within the available scope
        if (!$this->scopeRepository->areRequestedScopesAvailable($scope, $availableScope)) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2Exception::ERROR_INVALID_SCOPE,
                    'error_description' => sprintf('An unsupported scope was requested. Available scopes are %s.', implode(', ', $availableScope)),
                ]
            );
        }

        $grantTypeData = $grantTypeData->withScopes($scope);

        return $grantTypeData;

        //return $next($request, $grantTypeData, $grantType);
    }
}
