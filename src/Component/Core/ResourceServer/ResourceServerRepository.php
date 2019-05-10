<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Core\ResourceServer;

interface ResourceServerRepository
{
    /**
     * @param ResourceServerId $resourceServerId The resource server
     *
     * @return ResourceServer|null Return the resource server or null if the argument is not a valid resource server ID
     */
    public function find(ResourceServerId $resourceServerId): ?ResourceServer;
}
