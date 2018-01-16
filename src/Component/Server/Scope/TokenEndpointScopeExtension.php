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

namespace OAuth2Framework\Component\Server\Scope;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Server\TokenEndpoint\Extension\TokenEndpointExtension;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantTypeData;
use OAuth2Framework\Component\Server\TokenEndpoint\GrantType;
use OAuth2Framework\Component\Server\Scope\Policy\ScopePolicyManager;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class TokenEndpointScopeExtension implements TokenEndpointExtension
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
    public function beforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType, callable $next): GrantTypeData
    {
        /** @var GrantTypeData $grantTypeData */
        $grantTypeData = $next($request, $grantTypeData, $grantType);
        $params = $request->getParsedBody() ?? [];
        if (!array_key_exists('scope', $params)) {
            $scope = $grantTypeData->getAvailableScopes() ?? [];
        } else {
            $scope = explode(' ', $params['scope']);
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

    /**
     * {@inheritdoc}
     */
    public function afterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array
    {
        return $next($client, $resourceOwner, $accessToken);
    }
}
