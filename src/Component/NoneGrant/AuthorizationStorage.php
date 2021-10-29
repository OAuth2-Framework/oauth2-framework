<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\NoneGrant;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

/**
 * Interface AuthorizationRepository.
 */
interface AuthorizationStorage
{
    public function save(AuthorizationRequest $authorization): void;
}
