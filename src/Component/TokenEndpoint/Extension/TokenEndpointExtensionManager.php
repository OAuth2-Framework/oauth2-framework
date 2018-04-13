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

namespace OAuth2Framework\Component\TokenEndpoint\Extension;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use OAuth2Framework\Component\TokenEndpoint\GrantType;
use OAuth2Framework\Component\TokenEndpoint\GrantTypeData;
use Psr\Http\Message\ServerRequestInterface;

class TokenEndpointExtensionManager
{
    /**
     * @var TokenEndpointExtension[]
     */
    private $extensions = [];

    /**
     * @param TokenEndpointExtension $accessTokenParameterExtension
     *
     * @return TokenEndpointExtensionManager
     */
    public function add(TokenEndpointExtension $accessTokenParameterExtension): self
    {
        $this->extensions[] = $accessTokenParameterExtension;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @param GrantTypeData          $grantTypeData
     * @param GrantType              $grantType
     *
     * @return GrantTypeData
     *
     * @throws OAuth2Exception
     */
    public function handleBeforeAccessTokenIssuance(ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData
    {
        return call_user_func($this->getCallableBeforeAccessTokenIssuance(0), $request, $grantTypeData, $grantType);
    }

    /**
     * @param Client        $client
     * @param ResourceOwner $resourceOwner
     * @param AccessToken   $accessToken
     *
     * @return array
     *
     * @throws OAuth2Exception
     */
    public function handleAfterAccessTokenIssuance(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array
    {
        return call_user_func($this->getCallableAfterAccessTokenIssuance(0), $client, $resourceOwner, $accessToken);
    }

    /**
     * @param int $index
     *
     * @return callable
     *
     * @throws OAuth2Exception
     */
    private function getCallableBeforeAccessTokenIssuance(int $index): callable
    {
        if (!isset($this->extensions[$index])) {
            return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType): GrantTypeData {
                return $grantTypeData;
            };
        }
        $extension = $this->extensions[$index];

        return function (ServerRequestInterface $request, GrantTypeData $grantTypeData, GrantType $grantType) use ($extension, $index): GrantTypeData {
            return $extension->beforeAccessTokenIssuance($request, $grantTypeData, $grantType, $this->getCallableBeforeAccessTokenIssuance($index + 1));
        };
    }

    /**
     * @param int $index
     *
     * @return callable
     *
     * @throws OAuth2Exception
     */
    private function getCallableAfterAccessTokenIssuance(int $index): callable
    {
        if (!array_key_exists($index, $this->extensions)) {
            return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
                return $accessToken->getResponseData();
            };
        }
        $extension = $this->extensions[$index];

        return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken) use ($extension, $index): array {
            return $extension->afterAccessTokenIssuance($client, $resourceOwner, $accessToken, $this->getCallableAfterAccessTokenIssuance($index + 1));
        };
    }
}
