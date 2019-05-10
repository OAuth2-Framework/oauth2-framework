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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServer as ResourceServerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository as ResourceServerRepositoryInterface;

final class ResourceServerRepository implements ResourceServerRepositoryInterface
{
    public function find(ResourceServerId $resourceServerId): ?ResourceServerInterface
    {
        if ('http://foo.com' === $resourceServerId->getValue()) {
            return new ResourceServer($resourceServerId);
        }

        return null;
    }
}
