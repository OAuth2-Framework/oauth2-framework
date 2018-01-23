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

final class LoginParameterChecker implements UserAccountDiscovery
{
    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        if ($authorization->hasPrompt('login') && !$authorization->isUserAccountFullyAuthenticated()) {
            throw new RedirectToLoginPageException($authorization);
        }

        return $authorization;
    }
}
