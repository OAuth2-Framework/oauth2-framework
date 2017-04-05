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

namespace OAuth2Framework\Component\Server\Model\ResourceServer;

interface ResourceServerRepositoryInterface
{
    /**
     * @param ResourceServerId $resourceServer The resource server
     *
     * @return ResourceServerInterface|null Return the resource server or null if the argument is not a valid resource server ID
     */
    public function find(ResourceServerId $resourceServer): ? ResourceServerInterface;
}
