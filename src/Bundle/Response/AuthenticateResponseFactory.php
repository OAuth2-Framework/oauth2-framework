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

namespace OAuth2Framework\Bundle\Response;

use OAuth2Framework\Component\TokenEndpoint\AuthenticationMethod\AuthenticationMethodManager;

class AuthenticateResponseFactory extends \OAuth2Framework\Component\Core\Response\Factory\AuthenticateResponseFactory
{
    /**
     * @var AuthenticationMethodManager
     */
    private $tokenEndpointAuthMethodManager;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param AuthenticationMethodManager $tokenEndpointAuthMethodManager
     */
    public function __construct(AuthenticationMethodManager $tokenEndpointAuthMethodManager)
    {
        $this->tokenEndpointAuthMethodManager = $tokenEndpointAuthMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemes(): array
    {
        $schemes = [];
        foreach ($this->tokenEndpointAuthMethodManager->all() as $method) {
            $scheme = $method->getSchemesParameters();
            $schemes = array_merge($schemes, $scheme);
        }

        return $schemes;
    }
}
