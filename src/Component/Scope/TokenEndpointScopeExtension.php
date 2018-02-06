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

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

class TokenEndpointScopeExtension implements TokenEndpointExtension
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
     * ScopeProcessor constructor.
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
    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        /** @var GrantTypeData $grantTypeData */
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $scope = $this->getScope($request, $grantTypeData);
        $scope = $this->applyScopePolicy($scope, $grantTypeData->getClient());
        $this->checkRequestedScopeIsAvailable($scope, $grantTypeData);

        if (!empty($scope)) {
            $grantTypeData = $grantTypeData->withParameter('scope', $scope);
        }

        return $grantTypeData;
    }

    /**
     * {@inheritdoc}
     */
    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        return $next($client, $resourceOwner, $accessToken);
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     *
     * @return string
     */
    private function getScope(ServerRequestInterface $request, GrantTypeData $grantTypeData): string
    {
        $params = $request->getParsedBody() ?? [];
        if (!array_key_exists('scope', $params)) {
            return $grantTypeData->hasParameter('scope') ? $grantTypeData->getParameter('scope') : '';
        }

        return $params['scope'];
    }

    /**
     * @param string $scope
     * @param Client $client
     *
     * @return string
     *
     * @throws OAuth2Exception
     */
    private function applyScopePolicy(string $scope, Client $client): string
    {
        try {
            return $this->scopePolicyManager->apply($scope, $client);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_SCOPE, $e->getMessage(), $e);
        }
    }

    /**
     * @param string        $scope
     * @param GrantTypeData $grantTypeData
     *
     * @throws OAuth2Exception
     */
    private function checkRequestedScopeIsAvailable(string $scope, GrantTypeData $grantTypeData)
    {
        // The available scope can be limited by (in this order):
        // * the grant type (e.g. refresh token, authorization code parameter)
        // * the client configuration
        // * the scope repository
        $availableScope = $grantTypeData->hasParameter('scope') ? $grantTypeData->getParameter('scope') : $this->getAvailableScopesForClient($grantTypeData->getClient());
        $availableScopes = explode(' ', $availableScope);
        $requestedScopes = empty($scope) ? [] : explode(' ', $scope);
        $diff = array_diff($requestedScopes, $availableScopes);
        if (0 !== count($diff)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_SCOPE, sprintf('An unsupported scope was requested. Available scope is/are: %s.', implode(' ,', $availableScopes)));
        }
    }

    /**
     * @param Client $client
     *
     * @return string
     */
    private function getAvailableScopesForClient(Client $client): string
    {
        return ($client->has('scope')) ? $client->get('scope') : implode(' ', $this->scopeRepository->all());
    }
}
