<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class MaxAgeParameterAuthenticationChecker implements UserAuthenticationChecker
{
    public static function create(): static
    {
        return new self();
    }

    public function isAuthenticationNeeded(AuthorizationRequest $authorization): bool
    {
        if (! $authorization->hasUserAccount()) {
            return true;
        }
        $maxAge = null;
        if ($authorization->getClient()->has('default_max_age')) {
            $maxAge = (int) $authorization->getClient()
                ->get('default_max_age')
            ;
        }
        if ($authorization->hasQueryParam('max_age')) {
            $maxAge = (int) $authorization->getQueryParam('max_age');
        }

        if ($maxAge === null) {
            return false;
        }

        $lastLogin = $authorization->getUserAccount()
            ->getLastLoginAt()
        ;

        return $lastLogin === null || $maxAge < time() - $lastLogin->getTimestamp();
    }
}
