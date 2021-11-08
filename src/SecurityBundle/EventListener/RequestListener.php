<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\EventListener;

use OAuth2Framework\SecurityBundle\Security\Authentication\OAuth2Token;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;

class RequestListener
{
    public function __construct(
        private RequestStack $requestStack
    ) {
    }

    public function onKernelRequest(AuthenticationEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        $token = $event->getAuthenticationToken();
        if ($request === null || ! $token instanceof OAuth2Token) {
            return;
        }

        $request->attributes->set('oauth2_resource_owner_id', $token->getResourceOwnerId());
        $request->attributes->set('oauth2_client_id', $token->getClientId());
        $resourceServerId = $token->getAccessToken()
            ->getResourceServerId()
        ;
        $request->attributes->set('oauth2_resource_server_id', $resourceServerId?->getValue());
    }
}
