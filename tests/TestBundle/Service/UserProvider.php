<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Service;

use OAuth2Framework\Tests\TestBundle\Entity\UserAccount;
use OAuth2Framework\Tests\TestBundle\Repository\UserAccountRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private UserAccountRepository $userAccountRepository
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userAccountRepository->findOneByUsername($identifier);

        if ($user) {
            return $user;
        }

        throw new UserNotFoundException(sprintf('Username "%s" does not exist.', $identifier));
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof UserAccount) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUsername());
    }

    public function supportsClass($class): bool
    {
        return $class === UserAccount::class;
    }
}
