<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenRepository;
use OAuth2Framework\Component\Core\TokenType\TokenType;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Throwable;

final class OAuth2Provider implements AuthenticatorInterface
{
    private HttpMessageFactoryInterface $psrHttpFactory;

    public function __construct(
        private ?UserProviderInterface $userProvider,
        private TokenTypeManager $tokenTypeManager,
        private AccessTokenRepository $accessTokenRepository,
        private AuthenticationFailureHandlerInterface $failureHandler
    ) {
        $this->psrHttpFactory = new PsrHttpFactory(
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory()
        );
    }

    public function authenticate(Request $request): PassportInterface
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);
        $additionalCredentialValues = [];
        $tokenType = null;

        $token = $this->findToken($psrRequest, $additionalCredentialValues, $tokenType);

        if ($token === null) {
            throw new BadCredentialsException('OAuth2 authentication required. Invalid access token.');
        }
        $accessToken = $this->accessTokenRepository->find(AccessTokenId::create($token));

        if ($accessToken === null || $accessToken->hasExpired() || $accessToken->isRevoked()) {
            throw new BadCredentialsException('OAuth2 authentication required. Missing or invalid access token.');
        }
        if (! $tokenType instanceof TokenType) {
            throw new BadCredentialsException('OAuth2 authentication required. Missing or invalid access token.');
        }
        $isValid = $tokenType->isRequestValid($accessToken, $psrRequest, $additionalCredentialValues);
        if (! $isValid) {
            throw new BadCredentialsException('OAuth2 authentication required. Missing or invalid access token.');
        }

        $callback = $this->userProvider !== null ? null : static function (string $identifier): UserInterface {
            return new ResourceOwner($identifier);
        };

        return new Passport(
            new UserBadge($accessToken->getResourceOwnerId()->getValue(), $callback),
            AccessTokenBadge::create($accessToken)
        );
    }

    public function supports(Request $request): ?bool
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);
        $additionalCredentialValues = [];
        $tokenType = null;
        $token = $this->findToken($psrRequest, $additionalCredentialValues, $tokenType);

        return $token !== null;
    }

    public function createAuthenticatedToken(PassportInterface $passport, string $firewallName): TokenInterface
    {
        $accessTokenBadge = $passport->getBadge(AccessTokenBadge::class);
        if (! $accessTokenBadge instanceof AccessTokenBadge) {
            throw new BadCredentialsException();
        }
        try {
            $user = $this->userProvider->loadUserByIdentifier(
                $accessTokenBadge->getAccessToken()
                    ->getResourceOwnerId()
                    ->getValue()
            );
        } catch (\Throwable) {
            $user = new ResourceOwner($accessTokenBadge->getAccessToken()->getResourceOwnerId()->getValue());
        }

        $token = new OAuth2Token($accessTokenBadge->getAccessToken());
        $token->setUser($user);
        $token->setAuthenticated(true);

        return $token;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->failureHandler->onAuthenticationFailure($request, $exception);
    }

    private function findToken(
        ServerRequestInterface $request,
        array &$additionalCredentialValues = [],
        ?TokenType &$type = null
    ): ?string {
        try {
            return $this->tokenTypeManager->findToken($request, $additionalCredentialValues, $type);
        } catch (Throwable) {
            return null;
        }
    }
}
