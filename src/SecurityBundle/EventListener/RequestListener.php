<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\SecurityBundle\EventListener;

use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class RequestListener
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(AuthenticationEvent $event): void
    {
        $request = $this->requestStack->getMasterRequest();
        $token = $event->getAuthenticationToken();
        if (null === $request || !$token instanceof OAuth2Token) {
            return;
        }

        $request->attributes->set('oauth2_resource_owner_id', $token->getResourceOwnerId());
        $request->attributes->set('oauth2_client_id', $token->getClientId());
        $resourceServerId = $token->getAccessToken()->getResourceServerId();
        $request->attributes->set('oauth2_resource_server_id', $resourceServerId ? $resourceServerId->getValue() : null);
    }
}
