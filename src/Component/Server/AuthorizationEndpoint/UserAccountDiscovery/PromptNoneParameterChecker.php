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
use OAuth2Framework\Component\Server\AuthorizationEndpoint\Exception\CreateRedirectionException;
use OAuth2Framework\Component\Server\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ServerRequestInterface;

final class PromptNoneParameterChecker implements UserAccountDiscovery
{
    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization): Authorization
    {
        $userAccount = $authorization->getUserAccount();
        if (null === $userAccount && $authorization->hasPrompt('none')) {
            throw new CreateRedirectionException($authorization, OAuth2Exception::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.');
        }

        return $authorization;
    }
}
