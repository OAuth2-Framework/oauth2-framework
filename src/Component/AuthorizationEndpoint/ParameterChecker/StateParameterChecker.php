<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker;

use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;

/**
 * Class StateParameterChecker.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
 */
final class StateParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization)
    {
        if (true === $authorization->hasQueryParam('state')) {
            $authorization->setResponseParameter('state', $authorization->getQueryParam('state'));
        }
    }
}
