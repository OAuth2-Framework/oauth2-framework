<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\Consent;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

interface ConsentRepository
{
    /**
     * Returns true if the consent has been given earlier.
     * This method shall take into account:
     *     - the client ID,
     *     - the user account,
     *     - the scope,
     *     - the response type.
     *
     * Returns false if the consent has never been given.
     */
    public function hasConsentBeenGiven(AuthorizationRequest $authorizationRequest): bool;
}
