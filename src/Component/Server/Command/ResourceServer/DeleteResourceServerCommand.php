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

namespace OAuth2Framework\Component\Server\Command\ResourceServer;

use OAuth2Framework\Component\Server\Model\ResourceServer\ResourceServerId;

final class DeleteResourceServerCommand
{
    /**
     * @var ResourceServerId
     */
    private $resourceServerId;

    /**
     * DeleteResourceServerCommand constructor.
     *
     * @param ResourceServerId $resourceServerId
     */
    private function __construct(ResourceServerId $resourceServerId)
    {
        $this->resourceServerId = $resourceServerId;
    }

    /**
     * @param ResourceServerId $resourceServerId
     *
     * @return DeleteResourceServerCommand
     */
    public static function create(ResourceServerId $resourceServerId): DeleteResourceServerCommand
    {
        return new self($resourceServerId);
    }

    /**
     * @return ResourceServerId
     */
    public function getResourceServerId(): ResourceServerId
    {
        return $this->resourceServerId;
    }
}
