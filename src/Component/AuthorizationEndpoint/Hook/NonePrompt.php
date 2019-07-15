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

namespace OAuth2Framework\Component\AuthorizationEndpoint\Hook;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\Consent\ConsentRepository;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NonePrompt implements AuthorizationEndpointHook
{
    /**
     * @var null|ConsentRepository
     */
    private $consentRepository;

    public function __construct(?ConsentRepository $consentRepository)
    {
        $this->consentRepository = $consentRepository;
    }

    public function handle(ServerRequestInterface $request, string $authorizationRequestId, AuthorizationRequest $authorizationRequest): ?ResponseInterface
    {
        if (!$authorizationRequest->hasPrompt('none')) {
            return null;
        }

        $isConsentNeeded = null === $this->consentRepository ? true : !$this->consentRepository->hasConsentBeenGiven($authorizationRequest);
        if ($authorizationRequest->hasUserAccount()) {
            $this->handleWithAuthenticatedUser($authorizationRequest, $isConsentNeeded);

            return null;
        }

        $this->handleWithUnauthenticatedUser($authorizationRequest, $isConsentNeeded);

        return null;
    }

    private function handleWithAuthenticatedUser(AuthorizationRequest $authorizationRequest, bool $isConsentNeeded): void
    {
        if ($isConsentNeeded) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_INTERACTION_REQUIRED, 'The resource owner consent is required.', $authorizationRequest);
        }
        $authorizationRequest->allow();
    }

    private function handleWithUnauthenticatedUser(AuthorizationRequest $authorizationRequest, bool $isConsentNeeded): void
    {
        if ($isConsentNeeded) {
            throw new OAuth2AuthorizationException(OAuth2Error::ERROR_LOGIN_REQUIRED, 'The resource owner is not logged in.', $authorizationRequest);
        }
        $authorizationRequest->allow();
    }
}
