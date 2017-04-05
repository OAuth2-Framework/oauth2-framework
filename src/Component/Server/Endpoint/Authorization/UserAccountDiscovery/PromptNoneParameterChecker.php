<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Endpoint\Authorization\UserAccountDiscovery;

use OAuth2Framework\Component\Server\Endpoint\Authorization\Authorization;
use OAuth2Framework\Component\Server\Endpoint\Authorization\Exception\CreateRedirectionException;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use Psr\Http\Message\ServerRequestInterface;

final class PromptNoneParameterChecker implements UserAccountDiscoveryInterface
{
    /**
     * {@inheritdoc}
     */
    public function find(ServerRequestInterface $request, Authorization $authorization, callable $next): Authorization
    {
        $authorization = $next($request, $authorization);
        $userAccount = $authorization->getUserAccount();
        if (null === $userAccount && $authorization->hasPrompt('none')) {
            throw new CreateRedirectionException($authorization, OAuth2ResponseFactoryManager::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.');
        }

        return $authorization;
    }
}
