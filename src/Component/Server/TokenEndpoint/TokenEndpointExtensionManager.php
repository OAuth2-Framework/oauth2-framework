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

namespace OAuth2Framework\Component\Server\TokenEndpoint;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;
use OAuth2Framework\Component\Server\TokenEndpoint\Extension\TokenEndpointExtension;

final class TokenEndpointExtensionManager
{
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
     * @param Client        $client
     * @param ResourceOwner $resourceOwner
     * @param AccessToken   $accessToken
     *
     * @return array
     */
    public function process(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array
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
            return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken): array {
                return $accessToken->getResponseData();
            };
        }
        $extension = $this->extensions[$index];

        return function (Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken) use ($extension, $index): array {
            return $extension->process($client, $resourceOwner, $accessToken, $this->callableForNextExtension($index + 1));
        };
    }
}
