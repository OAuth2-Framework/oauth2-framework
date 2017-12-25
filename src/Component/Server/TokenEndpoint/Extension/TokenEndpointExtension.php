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

namespace OAuth2Framework\Component\Server\TokenEndpoint\Extension;

use OAuth2Framework\Component\Server\Core\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\ResourceOwner\ResourceOwner;

interface TokenEndpointExtension
{
    /**
     * @param Client        $client
     * @param ResourceOwner $resourceOwner
     * @param AccessToken   $accessToken
     * @param callable      $next
     *
     * @return array
     */
    public function process(Client $client, ResourceOwner $resourceOwner, AccessToken $accessToken, callable $next): array;
}
