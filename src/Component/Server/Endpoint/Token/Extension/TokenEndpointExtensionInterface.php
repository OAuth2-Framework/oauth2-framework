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

namespace OAuth2Framework\Component\Server\Endpoint\Token\Extension;

use OAuth2Framework\Component\Server\Model\AccessToken\AccessToken;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Model\ResourceOwner\ResourceOwnerInterface;

interface TokenEndpointExtensionInterface
{
    /**
     * @param Client                 $client
     * @param ResourceOwnerInterface $resourceOwner
     * @param AccessToken            $accessToken
     * @param callable               $next
     *
     * @return array
     */
    public function process(Client $client, ResourceOwnerInterface $resourceOwner, AccessToken $accessToken, callable $next): array;
}
