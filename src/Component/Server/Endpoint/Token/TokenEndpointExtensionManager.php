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

namespace OAuth2Framework\Component\Server\Endpoint\Token;

use OAuth2Framework\Component\Server\Endpoint\Token\Extension\TokenEndpointExtensionInterface;
use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;

final class TokenEndpointExtensionManager
{
    private $extensions = [];

    /**
     * @param TokenEndpointExtensionInterface $accessTokenParameterExtension
     *
     * @return TokenEndpointExtensionManager
     */
    public function add(TokenEndpointExtensionInterface $accessTokenParameterExtension): TokenEndpointExtensionManager
    {
        $this->extensions[] = $accessTokenParameterExtension;

        return $this;
    }

    /**
     * @param Client                 $client
     * @param ResourceOwnerInterface $resourceOwner
     * @param AccessToken            $accessToken
     *
     * @return array
     */
    public function process(Client $client, ResourceOwnerInterface $resourceOwner, AccessToken $accessToken): array
    {
        return call_user_func($this->callableForNextExtension(0), $client, $resourceOwner, $accessToken);
    }

    /**
     * @param int $index
     *
     * @return \Closure
     */
    private function callableForNextExtension($index)
    {
        if (!array_key_exists($index, $this->extensions)) {
            return function (Client $client, ResourceOwnerInterface $resourceOwner, AccessToken $accessToken): array {
                return $accessToken->getResponseData();
            };
        }
        $extension = $this->extensions[$index];

        return function (Client $client, ResourceOwnerInterface $resourceOwner, AccessToken $accessToken) use ($extension, $index): array {
            return $extension->process($client, $resourceOwner, $accessToken, $this->callableForNextExtension($index + 1));
        };
    }
}
