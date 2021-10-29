<?php

declare(strict_types=1);

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

/**
 * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
 */
final class StateParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void
    {
        if (! $authorization->hasQueryParam('state')) {
            return;
        }

        $authorization->setResponseParameter('state', $authorization->getQueryParam('state'));
    }
}
