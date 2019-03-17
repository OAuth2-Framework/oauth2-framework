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

namespace OAuth2Framework\SecurityBundle\Security\Authentication\Provider;

use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class OAuth2Provider implements AuthenticationProviderInterface
{
    public function authenticate(TokenInterface $token)
    {
        if (!$token instanceof OAuth2Token) {
            return;
        }
        $accessToken = $token->getAccessToken();

        if (true === $accessToken->hasExpired()) {
            throw new BadCredentialsException('The access token has expired.');
        }

        try {
            $token->setAuthenticated(true);

            return $token;
        } catch (\Throwable $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuth2Token;
    }
}
