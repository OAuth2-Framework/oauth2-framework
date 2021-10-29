<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Firewall;

use Nyholm\Psr7\Factory\Psr17Factory;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenHandlerManager;
use OAuth2Framework\Component\Core\AccessToken\AccessTokenId;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use OAuth2Framework\Component\Core\TokenType\TokenTypeManager;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

final class OAuth2Listener
{
    private HttpMessageFactoryInterface $httpMessageFactory;

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AuthenticationManagerInterface $authenticationManager,
        private TokenTypeManager $tokenTypeManager,
        private AccessTokenHandlerManager $accessTokenHandlerManager,
        private OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
    ) {
        $this->httpMessageFactory = new PsrHttpFactory(
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory(),
            new Psr17Factory()
        );
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $this->httpMessageFactory->createRequest($event->getRequest());

        try {
            $additionalCredentialValues = [];
            $accessTokenId = $this->tokenTypeManager->findToken($request, $additionalCredentialValues, $tokenType);
            if ($accessTokenId === null) {
                return;
            }
            // @var TokenType $tokenType
        } catch (Throwable $e) {
            return;
        }

        try {
            $accessToken = $this->accessTokenHandlerManager->find(new AccessTokenId($accessTokenId));
            if ($accessToken === null || $accessToken->isRevoked()) {
                throw new AuthenticationException('Invalid access token.');
            }
            if ($accessToken->hasExpired()) {
                throw new AuthenticationException('The access token expired.');
            }
            if (! $tokenType->isRequestValid($accessToken, $request, $additionalCredentialValues)) {
                throw new AuthenticationException('Invalid access token.');
            }

            $token = new OAuth2Token($accessToken);
            $result = $this->authenticationManager->authenticate($token);

            $this->tokenStorage->setToken($result);
        } catch (AuthenticationException $e) {
            $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
                OAuth2Error::accessDenied('OAuth2 authentication required. ' . $e->getMessage(), [], $e)
            );
            $factory = new HttpFoundationFactory();
            $event->setResponse($factory->createResponse($psr7Response));
        }
    }
}
