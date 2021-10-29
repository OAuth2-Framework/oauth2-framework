<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Repository;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServer as ResourceServerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerRepository as ResourceServerRepositoryInterface;
use OAuth2Framework\Tests\TestBundle\Entity\ResourceServer;

final class ResourceServerRepository implements ResourceServerRepositoryInterface
{
    public function find(ResourceServerId $resourceServerId): ?ResourceServerInterface
    {
        if ($resourceServerId->getValue() === 'http://foo.com') {
            return new ResourceServer($resourceServerId);
        }

        return null;
    }
}
