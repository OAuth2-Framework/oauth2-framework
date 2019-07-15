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

namespace OAuth2Framework\Component\OpenIdConnect\ParameterChecker;

use InvalidArgumentException;
use OAuth2Framework\Component\AuthorizationEndpoint\AuthorizationRequest\AuthorizationRequest;
use OAuth2Framework\Component\AuthorizationEndpoint\ParameterChecker\ParameterChecker;

final class NonceParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void
    {
        if (!$authorization->hasQueryParam('response_type')) {
            throw new InvalidArgumentException('The parameter "response_type" is mandatory.');
        }
        $response_type = explode(' ', $authorization->getQueryParam('response_type'));
        if (\in_array('id_token', $response_type, true) && !$authorization->hasQueryParam('nonce')) {
            throw new InvalidArgumentException('The parameter "nonce" is mandatory when the response type "id_token" is used.');
        }
    }
}
