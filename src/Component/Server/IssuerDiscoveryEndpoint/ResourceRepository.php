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

namespace OAuth2Framework\Component\Server\IssuerDiscoveryEndpoint;

interface ResourceRepository
{
    /**
     * @param ResourceId $resourceId
     *
     * @return Resource|null
     */
    public function find(ResourceId $resourceId): ?Resource;
}
