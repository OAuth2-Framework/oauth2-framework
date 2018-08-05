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

namespace OAuth2Framework\ServerBundle\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServer as ResourceServerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

class ResourceServer implements ResourceServerInterface
{
    private $resourceServerId;

    public function __construct(ResourceServerId $resourceServerId)
    {
        $this->resourceServerId = $resourceServerId;
    }

    public function getResourceServerId(): ResourceServerId
    {
        return $this->resourceServerId;
    }
}
