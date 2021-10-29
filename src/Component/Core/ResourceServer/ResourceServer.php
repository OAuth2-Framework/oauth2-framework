<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\ResourceServer;

interface ResourceServer
{
    public function getResourceServerId(): ResourceServerId;

    public function getAuthenticationMethod(): string;
}
