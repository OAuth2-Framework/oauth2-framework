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
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use OAuth2Framework\Component\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpointScopeExtension implements TokenEndpointExtension
{
    private $scopeRepository;
    private $scopePolicyManager;

    public function __construct(ScopeRepository $scopeRepository, ScopePolicyManager $scopePolicyManager)
    {
        $this->scopeRepository = $scopeRepository;
        $this->scopePolicyManager = $scopePolicyManager;
    }

    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        /** @var GrantTypeData $grantTypeData */
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $scope = $this->getScope($request, $grantTypeData);
        $scope = $this->applyScopePolicy($scope, $grantTypeData->getClient());
        $this->checkRequestedScopeIsAvailable($scope, $grantTypeData);
        if (!empty($scope)) {
            $grantTypeData->getParameter()->set('scope', $scope);
        }

        return $grantTypeData;
    }

    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        $result = $next($client, $resourceOwner, $accessToken);
        if ($accessToken->getParameter()->has('scope')) {
            $result['scope'] = $accessToken->getParameter()->get('scope');
        }

        return $result;
    }

    private function getScope(ServerRequestInterface $request, GrantTypeData $grantTypeData): string
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);

        switch (true) {
            case \array_key_exists('scope', $parameters):
                return $parameters['scope'];
            case $grantTypeData->getParameter()->has('scope'):
                return $grantTypeData->getParameter()->get('scope');
            case $grantTypeData->getMetadata()->has('scope'):
                return $grantTypeData->getMetadata()->get('scope');
            default:
                return '';
        }
    }

    private function applyScopePolicy(string $scope, Client $client): string
    {
        try {
            return $this->scopePolicyManager->apply($scope, $client);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_SCOPE, $e->getMessage(), [], $e);
        }
    }

    private function checkRequestedScopeIsAvailable(string $scope, GrantTypeData $grantTypeData): void
    {
        // The available scope can be limited by (in this order):
        // * the grant type (e.g. refresh token, authorization code parameter)
        // * the client configuration
        // * the scope repository
        $availableScope = $grantTypeData->getParameter()->has('scope') ? $grantTypeData->getParameter()->get('scope') : $this->getAvailableScopesForClient($grantTypeData->getClient());
        $availableScopes = \explode(' ', $availableScope);
        $requestedScopes = empty($scope) ? [] : \explode(' ', $scope);
        $diff = \array_diff($requestedScopes, $availableScopes);
        if (0 !== \count($diff)) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_SCOPE, \sprintf('An unsupported scope was requested. Available scope is/are: %s.', \implode(', ', $availableScopes)));
        }
    }

    private function getAvailableScopesForClient(Client $client): string
    {
        return ($client->has('scope')) ? $client->get('scope') : \implode(' ', $this->scopeRepository->all());
    }
}
