<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ConsentHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsentPrompt implements AuthorizationEndpointHook
{
    public function __construct(
        private ConsentHandler $consentHandler
    ) {
    }

    public function handle(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ?ResponseInterface {
        if (! $authorizationRequest->hasPrompt('consent')) {
            return null;
        }
        if ($authorizationRequest->hasConsentBeenGiven()) {
            return null;
        }

        return $this->consentHandler->handle($request, $authorizationRequestId);
    }
}
