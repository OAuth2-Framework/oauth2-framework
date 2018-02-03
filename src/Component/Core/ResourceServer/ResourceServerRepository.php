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

namespace OAuth2Framework\Component\Core\ResourceServer;

use OAuth2Framework\Component\IssuerDiscoveryEndpoint\ResourceId;

interface ResourceServerRepository
{
    /**
     * @param ResourceServerId $resourceServerId The resource server
     *
     * @return ResourceServer|null Return the resource server or null if the argument is not a valid resource server ID
     */
    public function find(ResourceServerId $resourceServerId): ? ResourceServer;

    /**
     * @param ResourceId $resourceId
     *
     * @return bool
     */
    public function supports(ResourceId $resourceId): bool;
}
