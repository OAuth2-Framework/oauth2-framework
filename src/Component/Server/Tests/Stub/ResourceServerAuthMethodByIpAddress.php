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

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerInterface;
use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ResourceServerAuthMethodByIpAddress implements TokenIntrospectionEndpointAuthMethodInterface
{
    /**
     * {@inheritdoc}
     */
    public function findResourceServerId(ServerRequestInterface $request, &$resourceServerCredentials = null): ?ResourceServerId
    {
        if ($request->hasHeader('X-Resource-Server-Id')) {
            $id = $request->getHeader('X-Resource-Server-Id');
            if (1 === count($id)) {
                return ResourceServerId::create($id[0]);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isResourceServerAuthenticated(ResourceServerInterface $resourceServer, $resourceServerCredentials, ServerRequestInterface $request): bool
    {
        if (!$resourceServer instanceof ResourceServer) {
            return false;
        }
        if ($resourceServer->isDeleted()) {
            return false;
        }
        if (!$resourceServer->has('ips') || !is_array($resourceServer->get('ips')) || empty($resourceServer->get('ips'))) {
            return false;
        }
        $params = $request->getServerParams();
        if (!array_key_exists('REMOTE_ADDR', $params)) {
            return false;
        }

        return in_array($params['REMOTE_ADDR'], $resourceServer->get('ips'));
    }
}
