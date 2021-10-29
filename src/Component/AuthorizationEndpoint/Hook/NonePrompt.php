<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NonePrompt implements AuthorizationEndpointHook
{
    public function __construct(
        private ?ConsentRepository $consentRepository
    ) {
    }

    public function handle(
        ServerRequestInterface $request,
        string $authorizationRequestId,
        AuthorizationRequest $authorizationRequest
    ): ?ResponseInterface {
        if (! $authorizationRequest->hasPrompt('none')) {
            return null;
        }

        $isConsentNeeded = $this->consentRepository === null || ! $this->consentRepository->hasConsentBeenGiven(
            $authorizationRequest
        );
        if ($authorizationRequest->hasUserAccount()) {
            $this->handleWithAuthenticatedUser($authorizationRequest, $isConsentNeeded);

            return null;
        }

        $this->handleWithUnauthenticatedUser($authorizationRequest, $isConsentNeeded);

        return null;
    }

    private function handleWithAuthenticatedUser(
        AuthorizationRequest $authorizationRequest,
        bool $isConsentNeeded
    ): void {
        if ($isConsentNeeded) {
            throw new OAuth2AuthorizationException(
                OAuth2Error::ERROR_INTERACTION_REQUIRED,
                'The resource owner consent is required.',
                $authorizationRequest
            );
        }
        $authorizationRequest->allow();
    }

    private function handleWithUnauthenticatedUser(
        AuthorizationRequest $authorizationRequest,
        bool $isConsentNeeded
    ): void {
        if ($isConsentNeeded) {
            throw new OAuth2AuthorizationException(
                OAuth2Error::ERROR_LOGIN_REQUIRED,
                'The resource owner is not logged in.',
                $authorizationRequest
            );
        }
        $authorizationRequest->allow();
    }
}
