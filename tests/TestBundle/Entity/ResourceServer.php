<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use OAuth2Framework\Component\Core\ResourceServer\ResourceServer as ResourceServerInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServerId;

final class ResourceServer implements ResourceServerInterface
{
    public function __construct(
        private ResourceServerId $resourceServerId
    ) {
    }

    public static function create(ResourceServerId $resourceServerId): self
    {
        return new self($resourceServerId);
    }

    public function getResourceServerId(): ResourceServerId
    {
        return $this->resourceServerId;
    }

    public function getAuthenticationMethod(): string
    {
        return 'none';
    }
}
