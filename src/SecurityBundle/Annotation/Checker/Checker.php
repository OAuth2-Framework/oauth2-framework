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

namespace OAuth2Framework\SecurityBundle\Annotation\Checker;

use OAuth2Framework\SecurityBundle\Annotation\OAuth2;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;

interface Checker
{
    /**
     * @param OAuth2Token $token
     * @param OAuth2      $configuration
     *
     * @throws \Exception
     */
    public function check(OAuth2Token $token, OAuth2 $configuration): void;
}
