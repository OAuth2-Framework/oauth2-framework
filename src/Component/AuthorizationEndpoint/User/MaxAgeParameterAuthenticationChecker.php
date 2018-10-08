<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\User;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

final class MaxAgeParameterAuthenticationChecker implements UserAuthenticationChecker
{
    public function isAuthenticationNeeded(AuthorizationRequest $authorization): bool
    {
        if (null === $authorization->getUserAccount()) {
            return true;
        }

        switch (true) {
            case $authorization->hasQueryParam('max_age'):
                $max_age = (int) $authorization->getQueryParam('max_age');

                break;
            case $authorization->getClient()->has('default_max_age'):
                $max_age = (int) $authorization->getClient()->get('default_max_age');

                break;
            default:
                return false;
        }

        return null === $authorization->getUserAccount()->getLastLoginAt() || \time() - $authorization->getUserAccount()->getLastLoginAt() > $max_age;
    }
}
