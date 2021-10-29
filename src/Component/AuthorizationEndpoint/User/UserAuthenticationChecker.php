<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

interface UserAuthenticationChecker
{
    public function isAuthenticationNeeded(AuthorizationRequest $authorization): bool;
}
