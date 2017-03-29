<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\Server\Tests\Stub;

use OAuth2Framework\Component\Server\Response\Factory\AuthenticateResponseFactory as Base;
use OAuth2Framework\Component\Server\TokenIntrospectionEndpointAuthMethod\TokenIntrospectionEndpointAuthMethodManager;

final class AuthenticateResponseFactoryForTokenIntrospection extends Base
{
    /**
     * @var TokenIntrospectionEndpointAuthMethodManager
     */
    private $tokenIntrospectionEndpointAuthMethodManager;

    /**
     * ClientAuthenticationMiddleware constructor.
     *
     * @param TokenIntrospectionEndpointAuthMethodManager $tokenIntrospectionEndpointAuthMethodManager
     */
    public function __construct(TokenIntrospectionEndpointAuthMethodManager $tokenIntrospectionEndpointAuthMethodManager)
    {
        $this->tokenIntrospectionEndpointAuthMethodManager = $tokenIntrospectionEndpointAuthMethodManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSchemes(): array
    {
        $schemes = [];
        foreach ($this->tokenIntrospectionEndpointAuthMethodManager->getTokenIntrospectionEndpointAuthMethods() as $method) {
            $scheme = $method->getSchemesParameters();
            $schemes = array_merge($schemes, $scheme);
        }

        return $schemes;
    }
}
