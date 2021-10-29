<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use Assert\Assertion;
use OAuth2Framework\Component\AuthorizationEndpoint\User\UserAccountDiscovery;
use OAuth2Framework\Component\Core\UserAccount\UserAccount;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class SymfonyUserAccountDiscovery implements UserAccountDiscovery
{
    public function __construct(
        private TokenStorageInterface $tokenStorage
    ) {
    }

    public function getCurrentAccount(): ?UserAccount
    {
        $token = $this->tokenStorage->getToken();
        Assertion::isInstanceOf($token, TokenInterface::class, 'Unable to retrieve the current user.');

        $user = $token->getUser();
        Assertion::isInstanceOf($user, UserAccount::class, 'Unable to retrieve the current user.');

        return $user;
    }
}
