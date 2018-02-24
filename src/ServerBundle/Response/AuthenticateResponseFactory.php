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

namespace OAuth2Framework\ServerBundle\Response;

use OAuth2Framework\Component\ClientAuthentication\AuthenticationMethodManager;
use OAuth2Framework\Component\Core\Response\Factory\AuthenticateResponseFactory as Base;

class AuthenticateResponseFactory extends Base
{
    /**
     * @var AuthenticationMethodManager
     */
    private $clientAuthenticationMethodManager;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param AuthenticationMethodManager $clientAuthenticationMethodManager
     */
    public function __construct(AuthenticationMethodManager $clientAuthenticationMethodManager)
    {
        $this->clientAuthenticationMethodManager = $clientAuthenticationMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemes(): array
    {
        $schemes = [];
        foreach ($this->clientAuthenticationMethodManager->all() as $method) {
            $scheme = $method->getSchemesParameters();
            $schemes = array_merge($schemes, $scheme);
        }

        return $schemes;
    }
}
