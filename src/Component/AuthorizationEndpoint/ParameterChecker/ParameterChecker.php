<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

interface ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void;
}
