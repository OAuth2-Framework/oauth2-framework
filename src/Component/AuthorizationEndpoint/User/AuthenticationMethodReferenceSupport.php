<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface AuthenticationMethodReferenceSupport
{
    /**
     * @return string[]|null
     */
    public function getAuthenticationMethodReferenceFor(UserAccount $userAccount): ?array;
}
