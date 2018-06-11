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

use OAuth2Framework\Component\AuthorizationEndpoint\Authorization;
use OAuth2Framework\Component\AuthorizationEndpoint\Exception\OAuth2AuthorizationException;
use OAuth2Framework\Component\Core\Message\OAuth2Message;

final class ClaimsParameterChecker implements ParameterChecker
{
    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization): Authorization
    {
        try {
            if ($authorization->hasQueryParam('claims')) {
                $decoded = json_decode($authorization->getQueryParam('claims'), true);
                if (!is_array($decoded)) {
                    throw new \InvalidArgumentException('Invalid "claims" parameter.');
                }

                return $authorization->withClaims($decoded);
            }

            return $authorization;
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2AuthorizationException(400, OAuth2Message::ERROR_INVALID_REQUEST, $e->getMessage(), $authorization, $e);
        }
    }
}