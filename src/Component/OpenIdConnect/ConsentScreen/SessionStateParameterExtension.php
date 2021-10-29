<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\OpenIdConnect\ConsentScreen;

use function in_array;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Extension\Extension;
use Psr\Http\Message\ServerRequestInterface;

abstract class SessionStateParameterExtension implements Extension
{
    public function process(ServerRequestInterface $request, AuthorizationRequest $authorization): void
    {
        if ($this->hasOpenIdScope($authorization)) {
            $browserState = $this->getBrowserState($request, $authorization);
            $sessionState = $this->calculateSessionState($request, $authorization, $browserState);
            $authorization->setResponseParameter('session_state', $sessionState);
        }
    }

    abstract protected function getBrowserState(
        ServerRequestInterface $request,
        AuthorizationRequest $authorization
    ): string;

    abstract protected function calculateSessionState(
        ServerRequestInterface $request,
        AuthorizationRequest $authorization,
        string $browserState
    ): string;

    private function hasOpenIdScope(AuthorizationRequest $authorization): bool
    {
        if (! $authorization->hasQueryParam('scope')) {
            return false;
        }

        $scope = $authorization->getQueryParam('scope');
        $scopes = explode(' ', $scope);

        return in_array('openid', $scopes, true);
    }
}
