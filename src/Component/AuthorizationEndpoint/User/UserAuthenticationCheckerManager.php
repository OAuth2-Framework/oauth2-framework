<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class UserAuthenticationCheckerManager
{
    /**
     * @var UserAuthenticationChecker[]
     */
    private array $checkers = [];

    public function add(UserAuthenticationChecker $checker): void
    {
        $this->checkers[] = $checker;
    }

    public function isAuthenticationNeeded(AuthorizationRequest $authorization): bool
    {
        foreach ($this->checkers as $checker) {
            if ($checker->isAuthenticationNeeded($authorization)) {
                return true;
            }
        }

        return false;
    }
}
