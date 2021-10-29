<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class MaxAgeParameterAuthenticationChecker implements UserAuthenticationChecker
{
    public function isAuthenticationNeeded(AuthorizationRequest $authorization): bool
    {
        if (! $authorization->hasUserAccount()) {
            return true;
        }

        switch (true) {
            case $authorization->hasQueryParam('max_age'):
                $max_age = (int) $authorization->getQueryParam('max_age');

                break;

            case $authorization->getClient()
                ->has('default_max_age'):
                $max_age = (int) $authorization->getClient()
                    ->get('default_max_age')
                ;

                break;

            default:
                return false;
        }

        return $authorization->getUserAccount()
            ->getLastLoginAt() === null || time() - $authorization->getUserAccount()
            ->getLastLoginAt() > $max_age;
    }
}
