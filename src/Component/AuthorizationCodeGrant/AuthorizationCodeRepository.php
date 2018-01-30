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

namespace OAuth2Framework\Component\AuthorizationCodeGrant;

interface AuthorizationCodeRepository
{
    /**
     * @param AuthorizationCode $authorizationCode
     */
    public function save(AuthorizationCode $authorizationCode);

    /**
     * Retrieve the stored data for the given authorization code.
     *
     * @param AuthorizationCodeId $authorizationCodeId the authorization code string for which to fetch data
     *
     * @return null|AuthorizationCode
     *
     * @see     http://tools.ietf.org/html/rfc6749#section-4.1
     */
    public function find(AuthorizationCodeId $authorizationCodeId): ? AuthorizationCode;
}
