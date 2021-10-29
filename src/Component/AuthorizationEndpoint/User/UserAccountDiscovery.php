<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\Core\UserAccount\UserAccount;

interface UserAccountDiscovery
{
    public function getCurrentAccount(): ?UserAccount;
}
