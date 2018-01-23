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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\UserAccountDiscovery;

use OAuth2Framework\Component\Server\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Exception\RedirectToLoginPageException;
use Psr\Http\Message\ServerRequestInterface;

final class MaxAgeParameterChecker implements UserAccountDiscovery
{
    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        $userAccount = $authorization->getUserAccount();
        if (null !== $userAccount) {
            // Whatever the prompt is, if the max_age constraint is not satisfied, the user is redirected to the login page
            if ($authorization->hasQueryParam('max_age') && null !== $userAccount->getLastLoginAt() && time() - $userAccount->getLastLoginAt()->getTimestamp() > (int) $authorization->getQueryParam('max_age')) { //FIXME: check if the client has a default_max_age parameter
                throw new RedirectToLoginPageException($authorization);
            }
        }

        return $authorization;
    }
}
