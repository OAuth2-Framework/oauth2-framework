<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication\Provider;

use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Throwable;

final class OAuth2Provider implements AuthenticationProviderInterface
{
    public function authenticate(TokenInterface $token): TokenInterface
    {
        if (! $token instanceof OAuth2Token) {
            return $token;
        }
        $accessToken = $token->getAccessToken();

        if ($accessToken->hasExpired() === true) {
            throw new BadCredentialsException('The access token has expired.');
        }

        try {
            $token->setAuthenticated(true);

            return $token;
        } catch (Throwable $e) {
            throw new AuthenticationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof OAuth2Token;
    }
}
