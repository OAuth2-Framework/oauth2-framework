<?php

declare(strict_types=1);

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
