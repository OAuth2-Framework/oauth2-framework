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
