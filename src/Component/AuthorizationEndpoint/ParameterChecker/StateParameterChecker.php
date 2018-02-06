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

/**
 * Class StateParameterChecker.
 *
 * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
 */
class StateParameterChecker implements ParameterChecker
{
    /**
     * {@inheritdoc}
     */
    public function check(Authorization $authorization): Authorization
    {
        if (true === $authorization->hasQueryParam('state')) {
            $authorization = $authorization->withResponseParameter('state', $authorization->getQueryParam('state'));
        }

        return $authorization;
    }
}
