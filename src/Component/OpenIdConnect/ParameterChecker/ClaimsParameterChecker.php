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
use function Safe\json_decode;

final class ClaimsParameterChecker implements ParameterChecker
{
    public function check(AuthorizationRequest $authorization): void
    {
        if (!$authorization->hasQueryParam('claims')) {
            return;
        }
        $decoded = json_decode($authorization->getQueryParam('claims'), true);
        if (!\is_array($decoded)) {
            throw new InvalidArgumentException('Invalid "claims" parameter.');
        }

        $authorization->getMetadata()->set('claims', $authorization->getQueryParam('claims'));
    }
}
