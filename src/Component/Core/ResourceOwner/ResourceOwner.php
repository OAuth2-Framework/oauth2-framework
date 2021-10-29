<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\ResourceOwner;

interface ResourceOwner
{
    public function getPublicId(): ResourceOwnerId;

    public function has(string $key): bool;

    /**
     * @return mixed|null
     */
    public function get(string $key);
}
