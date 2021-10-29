<?php

declare(strict_types=1);

namespace OAuth2Framework\Tests\TestBundle\Entity;

use function array_key_exists;
use DateTimeImmutable;
use InvalidArgumentException;
use OAuth2Framework\Component\Core\ResourceOwner\ResourceOwnerId;
use OAuth2Framework\Component\Core\UserAccount\UserAccount as UserAccountInterface;
use OAuth2Framework\Component\Core\UserAccount\UserAccountId;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

final class UserAccount implements UserAccountInterface, SymfonyUserInterface, EquatableInterface
{
    /**
     * @var string[]
     */
    private $roles;

    public function __construct(
        private UserAccountId $userAccountId,
        private string $username,
        array $roles,
        private ?DateTimeImmutable $lastLoginAt,
        private ?DateTimeImmutable $lastUpdateAt,
        private array $data
    ) {
        $this->roles = $roles;
    }

    public function getUserAccountId(): UserAccountId
    {
        return $this->userAccountId;
    }

    public function getPublicId(): ResourceOwnerId
    {
        return $this->userAccountId;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key)
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException(sprintf('The user account parameter "%s" does not exist.', $key));
        }

        return $this->data[$key];
    }

    public function getLastLoginAt(): ?int
    {
        return $this->lastLoginAt ? $this->lastLoginAt->getTimestamp() : null;
    }

    public function getLastUpdateAt(): ?int
    {
        return $this->lastUpdateAt ? $this->lastUpdateAt->getTimestamp() : null;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getSalt()
    {
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(SymfonyUserInterface $user): bool
    {
        if (! $user instanceof self) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
