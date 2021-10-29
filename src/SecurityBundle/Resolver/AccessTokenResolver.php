<?php

declare(strict_types=1);

namespace OAuth2Framework\SecurityBundle\Resolver;

use OAuth2Framework\Component\Core\AccessToken\AccessToken;
use OAuth2Framework\SecurityBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AccessTokenResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== AccessToken::class) {
            return false;
        }

        return $this->tokenStorage->getToken() instanceof OAuth2Token;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $token = $this->tokenStorage->getToken();
        if ($token instanceof OAuth2Token) {
            yield $token->getAccessToken();
        }
    }
}
