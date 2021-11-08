<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Security\Authentication;

use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Message\OAuth2MessageFactoryManager;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class DefaultFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private OAuth2MessageFactoryManager $oauth2ResponseFactoryManager
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $psr7Response = $this->oauth2ResponseFactoryManager->getResponse(
            OAuth2Error::accessDenied($exception->getMessage(), [], $exception)
        );
        $factory = new HttpFoundationFactory();

        return $factory->createResponse($psr7Response);
    }
}
