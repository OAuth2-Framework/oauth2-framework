<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\SelectAccountHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SelectAccountPrompt implements AuthorizationEndpointHook
{
    public function __construct(
        private SelectAccountHandler $selectAccountHandler
    ) {
    }

    public function handle(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ?ResponseInterface {
        if (! $authorizationRequest->hasPrompt('select_account')) {
            return null;
        }

        if ($authorizationRequest->hasAttribute(
            'account_has_been_selected'
        ) && $authorizationRequest->getAttribute('account_has_been_selected') === true) {
            return null;
        }

        return $this->selectAccountHandler->handle($request, $authorizationRequestId);
    }
}
