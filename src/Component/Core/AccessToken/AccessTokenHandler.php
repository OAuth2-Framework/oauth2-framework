<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\Core\AccessToken;

interface AccessTokenHandler
{
    public function find(AccessTokenId $token): ?AccessToken;
}
